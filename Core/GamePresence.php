<?php
// Per-game presence + inactivity clock store (spec docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md).
//
// ONE APCu entry per game, "presence_<gameName>", in the style of the chat store (SubmitChat.php).
// Deliberately NOT the "<gameName>" cache blob: SetCachePiece is an unlocked whole-blob
// read-modify-write and has already caused a race here (Core/EngineActionRunner.php:244-254).
// Deliberately NOT the gamestate: that would put the clock in the undo stack and in replays, and make
// every heartbeat a gamestate write that wakes every seat's poll.
//
// Shape:
//   v     int    bumped on every write (a poll wake condition in stage 2)
//   seen  [seat => unix]   last poll per seat
//   acted [seat => unix]   last CLOCK-RESETTING action per seat
//   owes  [seat, ...]      seats the game was waiting on as of the last action
//   since int              when the current wait began
//   votes [target => ['reason'=>'stall'|'disconnect','yes'=>[voter=>unix],'until'=>unix,'waits'=>int]]
//
// Every function is best-effort: no APCu (CLI SAPI) or a corrupt entry must degrade to "no data", never
// throw, because this is called from the action path. Losing the entry gives players MORE time, never less.
// TTL 3600s, refreshed on write — an hour-idle game loses it and the clock simply restarts.

const GAME_PRESENCE_TTL = 3600;

function PresenceCacheKey(string $gameName): string
{
    return 'presence_' . $gameName;
}

// 'pv' is the VOTE version: bumped only when a vote opens/changes/closes, never by a heartbeat.
// The poll wakes on 'pv', not 'v' — 'v' moves every couple of seconds per seat (the heartbeat), and
// waking on that would re-render every board every 2s.
// 'facts' caches what the evaluator needs (live seats, who is on the clock, the timeout) as of the last
// ACTION, so the poll's cheap path can evaluate and open a vote WITHOUT parsing the gamestate.
function PresenceDefault(): array
{
    return ['v' => 0, 'pv' => 0, 'seen' => [], 'acted' => [], 'owes' => [], 'since' => 0,
            'votes' => [], 'facts' => []];
}

function PresenceApcuReady(): bool
{
    return extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled()
        && function_exists('apcu_fetch') && function_exists('apcu_store');
}

function PresenceRead(string $gameName): array
{
    if (!PresenceApcuReady()) return PresenceDefault();
    $raw = apcu_fetch(PresenceCacheKey($gameName));
    if (!is_array($raw)) return PresenceDefault();
    return array_merge(PresenceDefault(), $raw);     // heals a partial/older shape
}

function PresenceWrite(string $gameName, array $p): bool
{
    if (!PresenceApcuReady()) return false;
    $p = array_merge(PresenceDefault(), $p);
    $p['v'] = intval($p['v']) + 1;
    return (bool)apcu_store(PresenceCacheKey($gameName), $p, GAME_PRESENCE_TTL);
}

function PresenceVersion(string $gameName): int
{
    return intval(PresenceRead($gameName)['v']);
}

// The heartbeat. Throttled so a 2.5s long-poll per seat does not write on every request.
function PresenceTouchSeat(string $gameName, int $seat, int $now, int $throttle = 2): bool
{
    if ($seat < 1 || !PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    $last = intval($p['seen'][$seat] ?? 0);
    if ($last > 0 && ($now - $last) < max(0, $throttle)) return false;
    $p['seen'][$seat] = $now;
    return PresenceWrite($gameName, $p);
}

// A clock-resetting action. $owes = seats the game is now waiting on (may be empty).
// Acting also cancels any open vote against this seat.
function PresenceStampAction(string $gameName, int $seat, int $now, array $owes = []): bool
{
    if ($seat < 1 || !PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    $p['acted'][$seat] = $now;
    $p['seen'][$seat]  = $now;
    $p['owes']  = array_values(array_unique(array_map('intval', $owes)));
    $p['since'] = $now;
    if (isset($p['votes'][$seat])) {                        // acting cancels the vote against you
        unset($p['votes'][$seat]);
        $p['pv'] = intval($p['pv'] ?? 0) + 1;
    }
    return PresenceWrite($gameName, $p);
}

// ── Vote state (stage 2) ──────────────────────────────────────────────────────────────────────────
// One entry per TARGET seat: a 4-seat game can have two people gone at once. Opening is idempotent,
// because every seat's poll evaluates and would otherwise re-open the same vote on every request.

// 'until' means "HIDDEN UNTIL" and is set only by a Wait press. A new vote must be visible at once, so
// it opens with until = 0 — setting it to now+timeout here hid every fresh prompt for 20s and also
// silently granted the stalling player an extension nobody asked for.
// $timeout is accepted for call-site symmetry with the wait length and is deliberately unused.
function PresenceOpenVote(string $gameName, int $target, string $reason, int $now, int $timeout = 0): bool
{
    if ($target < 1 || !PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    if (isset($p['votes'][$target])) return false;          // already open: never re-open, never re-reason
    $p['votes'][$target] = [
        'reason' => ($reason === 'disconnect') ? 'disconnect' : 'stall',
        'yes'    => [],
        'until'  => 0,
        'waits'  => 0,
        'opened' => $now,
    ];
    $p['pv'] = intval($p['pv'] ?? 0) + 1;
    return PresenceWrite($gameName, $p);
}

function PresenceRecordVote(string $gameName, int $target, int $voter, int $now): bool
{
    if ($target < 1 || $voter < 1 || $voter === $target || !PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    if (!isset($p['votes'][$target])) return false;
    $p['votes'][$target]['yes'][$voter] = $now;             // idempotent per voter
    $p['pv'] = intval($p['pv'] ?? 0) + 1;
    return PresenceWrite($gameName, $p);
}

// "Wait another 20 seconds": pushes the deadline out, keeps the sticky Yes votes.
function PresenceExtendVote(string $gameName, int $target, int $now, int $seconds): bool
{
    if ($target < 1 || !PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    if (!isset($p['votes'][$target])) return false;
    $p['votes'][$target]['until'] = $now + max(1, $seconds);
    $p['votes'][$target]['waits'] = intval($p['votes'][$target]['waits'] ?? 0) + 1;
    $p['pv'] = intval($p['pv'] ?? 0) + 1;
    return PresenceWrite($gameName, $p);
}

function PresenceClearVote(string $gameName, int $target): bool
{
    if (!PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    if (!isset($p['votes'][$target])) return false;
    unset($p['votes'][$target]);
    $p['pv'] = intval($p['pv'] ?? 0) + 1;
    return PresenceWrite($gameName, $p);
}

// ── Dev-environment opt-in (user request 2026-09-17) ─────────────────────────────────────────────
// The inactivity clock is OFF in the dev environment by default: a local tester poking at a board does
// not want a 75s countdown and a kick prompt appearing mid-session. The automated tests re-enable it
// PER GAME through SWUSim/DevTools/zz_presence_poke.php?clock=on. Production is unaffected — the gate
// only applies when SimGameIsDevelopmentEnvironment() is true.
function PresenceClockKey(string $gameName): string
{
    return 'presence_clockon_' . $gameName;
}

function PresenceClockEnabledInDev(string $gameName): bool
{
    if (!PresenceApcuReady()) return false;
    return apcu_fetch(PresenceClockKey($gameName)) === 1;
}

function PresenceSetClockEnabledInDev(string $gameName, bool $on): bool
{
    if (!PresenceApcuReady()) return false;
    if (!$on) { apcu_delete(PresenceClockKey($gameName)); return true; }
    return (bool)apcu_store(PresenceClockKey($gameName), 1, GAME_PRESENCE_TTL);
}

// A wait extension that has run out is a real EVENT: it must bump the vote version, or no poll wakes
// and every client goes on showing the prompt as hidden. Clears 'until' once, keeping the sticky votes.
function PresenceLapseExtensions(string $gameName, int $now): bool
{
    if (!PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    $changed = false;
    foreach (($p['votes'] ?? []) as $target => $v) {
        if (intval($v['until'] ?? 0) > 0 && $now >= intval($v['until'])) {
            $p['votes'][$target]['until'] = 0;
            $changed = true;
        }
    }
    if (!$changed) return false;
    $p['pv'] = intval($p['pv'] ?? 0) + 1;
    return PresenceWrite($gameName, $p);
}

// Poll wake condition, mirroring HasChatUpdate (Core/NetworkingLibraries.php). Keyed on the VOTE
// version so a heartbeat never wakes anybody.
function PresenceVoteVersion(string $gameName): int
{
    return intval(PresenceRead($gameName)['pv'] ?? 0);
}

function PresenceHasVoteUpdate(string $gameName, $lastVersion): bool
{
    return intval($lastVersion) < PresenceVoteVersion($gameName);
}

// Cache the evaluator's inputs (written on each action), so the poll's cheap path needs no gamestate.
function PresenceWriteFacts(string $gameName, array $facts): bool
{
    if (!PresenceApcuReady()) return false;
    $p = PresenceRead($gameName);
    $p['facts'] = $facts;
    return PresenceWrite($gameName, $p);
}
