<?php
// Inactivity clock — SWUSim policy (spec docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md).
// Stage 1: the fingerprint + "who is on the clock". No UI, no kick (stage 2).

const SWU_CLOCK_SECONDS_2P          = 75;   // head-to-head
const SWU_CLOCK_SECONDS_MULTI       = 150;  // Twin Suns / Team Suns
const SWU_CLOCK_DISCONNECT_SECONDS  = 30;   // silence before a seat counts as gone (user decision 2026-09-17)
const SWU_CLOCK_WAIT_SECONDS        = 20;   // "wait another 20 seconds" (stage 2)
const SWU_CLOCK_WARN_SECONDS        = 20;   // countdown appears with this much left (stage 3)

function SWUClockTimeout(): int
{
    return (SeatCountForGame() > 2) ? SWU_CLOCK_SECONDS_MULTI : SWU_CLOCK_SECONDS_2P;
}

// The clock never runs in local modes, in a replay, or after the game is over.
// ⚠ It is also OFF THROUGHOUT THE DEV ENVIRONMENT by default (user request 2026-09-17) so local testing
// is never interrupted by a countdown or a kick prompt. The automated tests opt a single game back in via
// SWUSim/DevTools/zz_presence_poke.php?clock=on. Production has no such gate.
function SWUClockIsActive(): bool
{
    if (function_exists('SimGameIsDevelopmentEnvironment') && SimGameIsDevelopmentEnvironment()
        && function_exists('PresenceClockEnabledInDev')) {
        global $gameName;
        if (!PresenceClockEnabledInDev(strval($gameName))) return false;
    }
    if (function_exists('SWUGameMode') && SWUGameMode() !== '') return false;   // goldfish/hotseat/botpractice
    if (function_exists('IsReplay') && IsReplay()) return false;
    $winner = DecisionQueueController::GetVariable('GAMEOVER_WINNER');
    if ($winner !== null && strval($winner) !== '' && strval($winner) !== '0') return false;
    return true;
}

function SWUSeatOwesDecision(int $seat): bool
{
    if ($seat < 1) return false;
    $q = &GetDecisionQueue($seat);
    return !empty($q);
}

// Seats the game is WAITING ON: any seat owing a decision, else the turn player.
// A player waiting on someone else is never on the clock (user decision 2026-09-17).
function SWUSeatsOnTheClock(): array
{
    if (!SWUClockIsActive()) return [];
    $live = function_exists('GetLiveSeatsArray') ? GetLiveSeatsArray() : [1, 2];
    $live = array_map('intval', $live);
    $owing = [];
    foreach ($live as $seat) {
        if (SWUSeatOwesDecision($seat)) $owing[] = $seat;
    }
    if (!empty($owing)) return $owing;
    $turn = intval(GetTurnPlayer());
    return in_array($turn, $live, true) ? [$turn] : [];
}

// ── The progress fingerprint ──────────────────────────────────────────────────────────────────────
// "Did the game move?" — hashed over ONLY the fields that mean progress, read from the already-parsed
// in-memory zones (no re-parse; unlike SWUBotComparableGamestateHash, which re-reads the whole file).
//
// ⚠ DELIBERATELY EXCLUDED, because a REFUSED action changes them and would fake progress:
//   FlashMessage (every refusal sets one), the undo stack / Versions, MatchReplayCommands, GameLog,
//   and SWU_ACTION_ID (bumped by SaveUndoVersion BEFORE the verb is attempted).
// ⚠ Also excluded: anything time-based, so the same board always hashes the same.
function SWUProgressFingerprint(): string
{
    $parts = [];
    $parts[] = 'ph:' . strval(GetCurrentPhase());
    $parts[] = 'tp:' . intval(GetTurnPlayer());
    $parts[] = 'ic:' . strval(GetInitiativeCounter());
    if (function_exists('GetBlastCounter')) $parts[] = 'bc:' . strval(GetBlastCounter());
    if (function_exists('GetPlanCounter'))  $parts[] = 'pc:' . strval(GetPlanCounter());
    $parts[] = 'ls:' . implode('', GetLiveSeatsArray());

    for ($s = 1; $s <= SeatCountForGame(); ++$s) {
        $hand = &GetHand($s); $deck = &GetDeck($s); $disc = &GetDiscard($s); $res = &GetResources($s);
        $parts[] = "s$s:h" . count($hand) . 'd' . count($deck) . 'x' . count($disc) . 'r' . count($res);
        // Resource readiness matters (paying a cost exhausts one).
        $ready = 0;
        foreach ($res as $r) { if (intval($r->Status ?? 0) === 1) ++$ready; }
        $parts[] = "s$s:rr" . $ready;
        foreach (['GroundArena', 'SpaceArena'] as $arena) {
            $zone = ($arena === 'GroundArena') ? GetGroundArena($s) : GetSpaceArena($s);
            $units = [];
            foreach ($zone as $u) {
                if (!empty($u->removed)) continue;
                $units[] = strval($u->CardID) . ':' . intval($u->Status) . ':' . intval($u->Damage)
                         . ':' . count($u->Subcards ?? []) . ':' . intval($u->Controller ?? 0);
            }
            $parts[] = "s$s:$arena:" . implode(',', $units);
        }
        $base = &GetBase($s);
        $parts[] = "s$s:base" . (isset($base[0]) ? intval($base[0]->Damage) : 0);
        $lead = &GetLeader($s);
        foreach ($lead as $li => $l) {
            $parts[] = "s$s:l$li:" . strval($l->Deployed ?? '') . ':' . strval($l->EpicActionUsed ?? '')
                     . ':' . intval($l->Status ?? 0);
        }
    }
    return md5(implode('|', $parts));
}

// ── Removal (stage 2) ─────────────────────────────────────────────────────────────────────────────
// Mirrors what mode 10006 (concede) does, so the stats chain in EngineActionRunner's write block runs
// untouched: captureGameDetail needs the LIVE gamestate, so this must happen inside the voting
// request, never from a poll or a side endpoint.
// ⚠ This replaced Core's MatchInactivityForfeit(), deleted 2026-09-17: it was 2-seat only and bypassed
// TriggerGameOver()'s Twin Suns branch, so it would have handed a 4-seat game to one seat.
function SWUApplyKick(int $target): void
{
    $name = 'P' . $target;
    AddGameLogEntry('CONCEDE', "$name was removed for inactivity", 'ALL');

    if (SeatCountForGame() <= 2) {
        TriggerGameOver($target);                      // declares the opponent the winner
        return;
    }
    if (function_exists('SWUIsTeamGame') && SWUIsTeamGame()) {
        SWUDeclareTwinSunsWinners(SWUVoterSeatsFor($target), "$name was removed for inactivity");
        return;
    }
    // Twin Suns free-for-all: the seat leaves and NOBODY heals — a null killer skips the CR §12.6.2
    // heal, which is for a defeat by an opponent, not an administrative removal. User decision 2026-09-17.
    SWUEliminateSeat($target, null);
}

// ── Facts + the per-viewer payload (stage 2) ──────────────────────────────────────────────────────

// Everything SWUPresenceEvaluate needs, read from the loaded gamestate.
// Bot seats: GetSWUBotPlayers() returns [] outside botpractice, and the clock is already inert there —
// so this is belt-and-braces. An Arenabot game therefore has no clock at all, which is correct.
function SWUClockFacts(): array
{
    return [
        'seats'   => array_map('intval', GetLiveSeatsArray()),
        'onClock' => SWUSeatsOnTheClock(),
        'timeout' => SWUClockTimeout(),
        'active'  => SWUClockIsActive(),
        'bots'    => function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [],
        // ⚠ Load-bearing for the poll's CHEAP path: the vote rules below must not call
        // SWUIsTeamGame()/SeatCountForGame() there, because no gamestate is parsed on that path.
        'team'    => (function_exists('SWUIsTeamGame') && SWUIsTeamGame()),
        'count'   => SeatCountForGame(),
    ];
}

// Display name for a seat: the real username when the game has a match record and that seat is logged
// in, else P<seat>. ⚠ Gate on userId > 0, never on the display string — MatchSeatDisplayNames
// substitutes "Player N" for a guest (OTMTCGE memory swu-seat-usernames-consumer-without-producer).
function SWUClockSeatName(string $gameName, int $seat): string
{
    if (!function_exists('SWUReadMatchRef') || !function_exists('MatchSeatDisplayNames')) return 'P' . $seat;
    $ref = SWUReadMatchRef($gameName);
    if ($ref === null || empty($ref['matchId'])) return 'P' . $seat;
    $match = SWUReadMatch($ref['matchId']);
    if (!is_array($match) || empty($match['players'][strval($seat)])) return 'P' . $seat;
    if (intval($match['players'][strval($seat)]['userId'] ?? 0) <= 0) return 'P' . $seat;
    $names = MatchSeatDisplayNames($match);
    $name = strval($names[$seat] ?? '');
    return ($name !== '') ? $name : ('P' . $seat);
}

// The block each viewer gets on the poll. Opens any needed vote first (idempotent — every seat's poll
// runs this, so PresenceOpenVote must not re-open or re-reason an existing vote).
// $viewerSeat is 0 for a spectator: they see the clock, never a button.
// LIVE path (the poll has parsed the gamestate): refresh the cached facts, then build.
function SWUPresencePayload(string $gameName, int $viewerSeat, int $now): array
{
    $facts = SWUClockFacts();
    PresenceWriteFacts($gameName, $facts);
    // Seed the clock the first time a live game is observed. Without this a player who NEVER acts is
    // unkickable: the evaluator refuses to expire a seat with no 'acted' and no 'since' (by design, so
    // an empty store can never expire anyone), and nothing else would ever set 'since'.
    if (!empty($facts['active'])) {
        $p = PresenceRead($gameName);
        if (intval($p['since']) === 0 && empty($p['acted'])) {
            $p['since'] = $now;
            PresenceWrite($gameName, $p);
        }
    }
    return SWUPresenceBuild($gameName, $facts, $viewerSeat, $now);
}

// CHEAP path (before ParseGamestate): use the facts cached by the last action. Returns an empty block
// when no action has happened yet — the first full-board poll seeds the facts.
function SWUPresenceLight(string $gameName, int $viewerSeat, int $now): array
{
    $p = PresenceRead($gameName);
    $facts = is_array($p['facts'] ?? null) ? $p['facts'] : [];
    if (empty($facts)) return ['v' => intval($p['pv'] ?? 0), 'onClock' => null, 'vote' => null];
    return SWUPresenceBuild($gameName, $facts, $viewerSeat, $now);
}

function SWUPresenceBuild(string $gameName, array $facts, int $viewerSeat, int $now): array
{
    if (empty($facts['active'])) {
        return ['v' => PresenceVoteVersion($gameName), 'onClock' => null, 'vote' => null];
    }

    // Turn a lapsed "wait another 20 seconds" into a real change, so the long poll wakes and the prompt
    // comes back for everyone (without it the extension silently became permanent).
    PresenceLapseExtensions($gameName, $now);

    $verdict = SWUPresenceEvaluate(PresenceRead($gameName), $facts, $now);
    foreach ($verdict['needVote'] as $target => $reason) {
        PresenceOpenVote($gameName, intval($target), strval($reason), $now, SWU_CLOCK_WAIT_SECONDS);
    }
    $presence = PresenceRead($gameName);
    $verdict  = SWUPresenceEvaluate($presence, $facts, $now);

    // The clock line: whichever on-clock seat has least time left.
    $onClock = null;
    foreach ($verdict['clock'] as $seat => $c) {
        if ($onClock === null || $c['remaining'] < $onClock['remaining']) {
            $onClock = ['seat' => intval($seat), 'remaining' => intval($c['remaining']),
                        'warn' => $c['remaining'] <= SWU_CLOCK_WARN_SECONDS,
                        'name' => SWUClockSeatName($gameName, intval($seat))];
        }
    }

    // One vote at a time, longest-stalled first, so the overlay never stacks.
    $vote = null;
    $votes = is_array($presence['votes']) ? $presence['votes'] : [];
    uasort($votes, function ($a, $b) { return intval($a['opened'] ?? 0) <=> intval($b['opened'] ?? 0); });
    foreach ($votes as $target => $v) {
        $target = intval($target);
        // A "Wait another 20 seconds" press hides the prompt for everyone until the extension lapses,
        // WITHOUT clearing the sticky Yes votes (they live on in the store).
        if ($now < intval($v['until'] ?? 0)) continue;
        $voters = SWUVoterSeatsFor($target, $facts);
        $vote = [
            'target'       => $target,
            'name'         => SWUClockSeatName($gameName, $target),
            'reason'       => strval($v['reason'] ?? 'stall'),
            'yes'          => array_values(array_map('intval', array_keys($v['yes'] ?? []))),
            'needed'       => SWUVotesNeededFor($target, $facts),
            'voters'       => $voters,
            'canVote'      => $viewerSeat >= 1 && in_array($viewerSeat, $voters, true),
            'youVoted'     => $viewerSeat >= 1 && isset($v['yes'][$viewerSeat]),
            'waitSeconds'  => SWU_CLOCK_WAIT_SECONDS,
        ];
        break;
    }
    // 'v' is the VOTE version: what the client echoes back as lastPresenceVersion.
    return ['v' => intval($presence['pv'] ?? 0), 'onClock' => $onClock, 'vote' => $vote];
}

// ── Kick-vote rules (stage 2) ─────────────────────────────────────────────────────────────────────
// User decisions 2026-09-17: 2P = the one opponent decides. Twin Suns = 2 Yes votes (3 live seats is
// therefore unanimous, 4 live is 2 of 3). Team Suns = BOTH opposing-team players, and the target's
// teammate is never asked, because a kick hands that team the win.

// $facts (from PresenceRead()['facts']) makes these usable on the poll's CHEAP path, where the gamestate
// is NOT parsed — without it GetLiveSeatsArray()/SeatCountForGame() answer with engine defaults (2 seats)
// and a 4-seat game is evaluated as head-to-head. Pass null only where the gamestate IS loaded.
function SWUVoterSeatsFor(int $target, ?array $facts = null): array
{
    $seats = ($facts !== null && !empty($facts['seats']))
        ? array_map('intval', $facts['seats'])
        : array_map('intval', GetLiveSeatsArray());
    $isTeam = ($facts !== null)
        ? !empty($facts['team'])
        : (function_exists('SWUIsTeamGame') && SWUIsTeamGame());
    $live = array_values(array_filter($seats, function ($s) use ($target) { return $s !== $target; }));
    if ($isTeam) {
        // Seat parity IS the team (1,3 red · 2,4 blue) — computed directly, because SWUTeamOf() consults
        // SWUIsTeamGame() and would degrade to "every seat its own team" on the cheap path.
        $live = array_values(array_filter($live, function ($s) use ($target) {
            return ($s % 2) !== ($target % 2);               // the OPPOSING team only
        }));
    }
    sort($live);
    return $live;
}

function SWUVotesNeededFor(int $target, ?array $facts = null): int
{
    $voters = SWUVoterSeatsFor($target, $facts);
    if (empty($voters)) return 0;                            // nobody left to decide
    $count = ($facts !== null && !empty($facts['count'])) ? intval($facts['count']) : SeatCountForGame();
    $isTeam = ($facts !== null)
        ? !empty($facts['team'])
        : (function_exists('SWUIsTeamGame') && SWUIsTeamGame());
    if ($count <= 2) return 1;
    if ($isTeam) return count($voters);                      // unanimous
    return min(2, count($voters));                           // Twin Suns: 2 (unanimous at 3 live seats)
}

// Only Yes votes from CURRENT legal voters count — a seat that has since been kicked, or the target
// itself, is discarded rather than carrying the vote on a stale tally.
function SWUVoteIsCarried(array $vote, int $target, ?array $facts = null): bool
{
    $needed = SWUVotesNeededFor($target, $facts);
    if ($needed <= 0) return false;
    $legal = SWUVoterSeatsFor($target, $facts);
    $yes = 0;
    foreach (array_keys($vote['yes'] ?? []) as $voter) {
        if (in_array(intval($voter), $legal, true)) ++$yes;
    }
    return $yes >= $needed;
}

// ── The evaluator ─────────────────────────────────────────────────────────────────────────────────
// PURE: presence store + facts + now in, verdict out. No APCu, no gamestate — so every rule is unit
// tested without sleeping or building a board (tests pass $now).
//
// $facts: ['seats'=>int[], 'onClock'=>int[], 'timeout'=>int, 'active'=>bool, 'bots'=>int[]]
// returns ['clock'=>[seat=>['deadline'=>int,'remaining'=>int,'expired'=>bool]],
//          'gone'=>int[], 'needVote'=>[seat=>'stall'|'disconnect']]
function SWUPresenceEvaluate(array $presence, array $facts, int $now): array
{
    $out = ['clock' => [], 'gone' => [], 'needVote' => []];
    if (empty($facts['active'])) return $out;

    $presence = array_merge(PresenceDefault(), $presence);
    $bots    = array_map('intval', $facts['bots'] ?? []);
    $seats   = array_map('intval', $facts['seats'] ?? []);
    $onClock = array_map('intval', $facts['onClock'] ?? []);
    $timeout = max(1, intval($facts['timeout'] ?? SWU_CLOCK_SECONDS_2P));
    $votes   = is_array($presence['votes']) ? $presence['votes'] : [];
    $since   = intval($presence['since']);

    // Disconnect: ANY seat that has stopped polling, on the clock or not (a quitter waiting their turn).
    // A seat that has never polled is not "gone" — it may not have opened the page yet (the lobby's rule).
    foreach ($seats as $seat) {
        if (in_array($seat, $bots, true)) continue;
        $seen = intval($presence['seen'][$seat] ?? 0);
        if ($seen > 0 && ($now - $seen) > SWU_CLOCK_DISCONNECT_SECONDS) $out['gone'][] = $seat;
    }

    foreach ($onClock as $seat) {
        if (in_array($seat, $bots, true)) continue;          // bots never stall
        // The clock starts when this seat's WAIT began: the later of its own last action and `since` (the last
        // clock-resetting action by anyone). Counting from acted[seat] alone charged a seat for every second it
        // spent waiting on the others — at four seats that is three turns, so a seat could come on the clock
        // already expired (player report 2026-09-21). A seat still deciding keeps its start: its own
        // non-stamping actions (e.g. declaring an attack) move neither value.
        $from = max(intval($presence['acted'][$seat] ?? 0), $since);
        if ($from <= 0) continue;                            // no data at all: never expire anyone
        $deadline = $from + $timeout;
        $until = intval($votes[$seat]['until'] ?? 0);        // a Wait extension postpones expiry
        if ($until > $deadline) $deadline = $until;
        $out['clock'][$seat] = [
            'deadline'  => $deadline,
            'remaining' => max(0, $deadline - $now),
            'expired'   => $now > $deadline,
        ];
    }

    // What needs a prompt that does not already have one. Disconnect wins over stall (better message).
    foreach ($out['gone'] as $seat) {
        if (!isset($votes[$seat])) $out['needVote'][$seat] = 'disconnect';
    }
    foreach ($out['clock'] as $seat => $c) {
        if ($c['expired'] && !isset($votes[$seat]) && !isset($out['needVote'][$seat])) {
            $out['needVote'][$seat] = 'stall';
        }
    }
    return $out;
}
