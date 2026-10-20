<?php
// Twin Suns rematch — the table-wide vote, as pure functions over a match array (no APCu, no HTTP, no files).
// Spec: docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
//   • voters = the humans seated at game start (eliminated included); bots never vote
//   • one choice per player: quick | room | '' (the other option switches, the same option withdraws)
//   • LEFT = announced Main Menu, removed for inactivity, or QUIET (no end-screen heartbeat for 120s)
//   • Quick needs every human and nobody LEFT; Room needs every non-LEFT human (at least one)
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

require_once __DIR__ . '/../../TableRematch.php';

// Seats 1-3 humans, seat 4 a bot.
function mkMatch() {
    return ['matchId' => 'MT', 'format' => 'twinsuns', 'state' => 'complete', 'players' => [
        '1' => ['authKey' => 'k1', 'username' => 'Ann'],
        '2' => ['authKey' => 'k2'],
        '3' => ['authKey' => 'k3', 'username' => 'Cy'],
        '4' => ['authKey' => 'k4', 'botProfile' => 'precon:x', 'username' => 'Arenabot Alpha'],
    ]];
}
$T = 1000;
$all = [1 => $T, 2 => $T, 3 => $T];   // everyone heard from

// ── Voters ─────────────────────────────────────────────────────────────────────────────────────
$m = mkMatch();
check(TableRematchHumanSeats($m) === [1, 2, 3], 'voters are the three humans; the bot is not one');
check(TableRematchSetChoice($m, 4, 'quick', $T) !== null, 'a bot seat cannot vote');
check(TableRematchSetChoice($m, 9, 'quick', $T) !== null, 'a seat that is not in the match cannot vote');

// ── Quick: everyone, nobody left ───────────────────────────────────────────────────────────────
$m = mkMatch();
TableRematchSetChoice($m, 1, 'quick', $T); TableRematchSetChoice($m, 2, 'quick', $T);
check(TableRematchOutcome($m, $all, $T) === '', '2 of 3 quick: nothing yet');
TableRematchSetChoice($m, 3, 'quick', $T);
check(TableRematchOutcome($m, $all, $T) === 'quick', '3 of 3 quick: quick rematch');

// ── One choice per player ──────────────────────────────────────────────────────────────────────
$m = mkMatch();
TableRematchSetChoice($m, 1, 'quick', $T);
TableRematchSetChoice($m, 1, 'room', $T);
check(($m['tableRematch']['choices']['1'] ?? null) === 'room', 'choosing the other option switches the vote');
TableRematchSetChoice($m, 1, 'room', $T);
check(!isset($m['tableRematch']['choices']['1']), 'choosing the same option again withdraws it');
check(TableRematchSetChoice($m, 1, 'nonsense', $T) !== null, 'an unknown choice is refused');

// ── A leaver: Quick is blocked, Room needs the others ─────────────────────────────────────────
$m = mkMatch();
TableRematchSetChoice($m, 1, 'quick', $T); TableRematchSetChoice($m, 3, 'quick', $T);
TableRematchSetChoice($m, 2, 'quick', $T);
TableRematchMarkLeft($m, 2, 'announced');
check(TableRematchLeftSeats($m, $all, $T) === [2], 'seat 2 has left');
check(!isset($m['tableRematch']['choices']['2']), "leaving clears the leaver's vote");
check(TableRematchOutcome($m, $all, $T) === '', 'Quick never fires once someone has left');
check(TableRematchSetChoice($m, 2, 'room', $T) !== null, 'a seat that left cannot vote');
TableRematchSetChoice($m, 1, 'room', $T); TableRematchSetChoice($m, 3, 'room', $T);
check(TableRematchOutcome($m, $all, $T) === 'room', 'Room fires with every human who stayed');
$v = TableRematchView($m, $all, $T, 1);
check($v['quick']['blocked'] === true, 'the view marks Quick blocked');
check($v['room']['needed'] === [1, 3] && $v['room']['votes'] === [1, 3], 'the view lists who Room needs and who voted');
check($v['left'] === [2] && $v['mine'] === 'room', 'the view shows the leaver and the viewer\'s own vote');
check(($v['names'][1] ?? '') === 'Ann' && ($v['names'][2] ?? '') === 'Player 2', 'names: the username, else Player N');

$m = mkMatch();
TableRematchMarkLeft($m, 3, 'kicked');
check(TableRematchLeftSeats($m, $all, $T) === [3], 'removed for inactivity counts as left');

// ── Quiet: no heartbeat for 120s ──────────────────────────────────────────────────────────────
$m = mkMatch();
TableRematchSetChoice($m, 1, 'room', $T);   // opens the vote at $T
$seen = [1 => $T + 110, 2 => $T + 110];      // seat 3 never polled the end screen
check(TableRematchLeftSeats($m, $seen, $T + 119) === [], 'quiet for 119s: still at the table');
check(TableRematchLeftSeats($m, $seen, $T + 121) === [3], 'quiet for 121s: counted as left');
$seen[3] = $T + 200;
check(TableRematchLeftSeats($m, $seen, $T + 210) === [], 'a quiet seat that polls again is back');
check(TableRematchLeftSeats($m, [1 => $T, 2 => $T, 3 => $T], $T + 500) === [1, 2, 3], 'everyone silent for long enough: all quiet');

// A QUIET seat keeps its vote (it may come back) — but Quick still needs the whole table PRESENT.
$m = mkMatch();
foreach ([1, 2, 3] as $s) TableRematchSetChoice($m, $s, 'quick', $T);
check(TableRematchOutcome($m, [1 => $T + 125, 2 => $T + 125, 3 => $T], $T + 130) === '', 'everyone voted Quick but seat 3 went quiet: Quick does not fire');

// The heartbeat store remembers when IT was (re)created: after a wipe, nobody counts as quiet until that is 120s old.
$m = mkMatch();
TableRematchSetChoice($m, 1, 'room', $T);
check(TableRematchLeftSeats($m, ['_since' => $T + 500, 1 => $T + 500], $T + 510) === [], 'a fresh heartbeat store (wiped at T+500) makes nobody quiet at T+510');
check(TableRematchLeftSeats($m, ['_since' => $T + 500, 1 => $T + 630], $T + 630) === [2, 3], '…but seats still silent 120s after the wipe are quiet');

// ── Everybody gone: nothing fires ─────────────────────────────────────────────────────────────
$m = mkMatch();
foreach ([1, 2, 3] as $s) TableRematchMarkLeft($m, $s, 'announced');
check(TableRematchOutcome($m, $all, $T) === '', 'all humans left: neither option fires (a bot-only table never rematches)');

// ── Fire once ─────────────────────────────────────────────────────────────────────────────────
$m = mkMatch();
foreach ([1, 2, 3] as $s) TableRematchSetChoice($m, $s, 'quick', $T);
$m['tableRematch']['claim'] = ['kind' => 'quick', 'at' => $T];
check(TableRematchOutcome($m, $all, $T) === '', 'once claimed, the outcome does not fire again');
check(TableRematchSetChoice($m, 1, '', $T) !== null, 'and votes are frozen');
check(TableRematchOutcome($m, $all, $T + 100) === 'quick', 'a claim that never produced a result (a fire that died) goes stale after 90s: the table can fire again');
$m['tableRematch']['result'] = ['kind' => 'quick', 'gameName' => '555'];
check(TableRematchOutcome($m, $all, $T + 100) === '', 'a stored result is permanent');

// ── The result, per viewer ────────────────────────────────────────────────────────────────────
$m = mkMatch();
$m['tableRematch'] = ['since' => $T, 'choices' => [], 'left' => ['2' => 'announced'],
    'claim' => ['kind' => 'room', 'at' => $T],
    'result' => ['kind' => 'room', 'lobbyID' => 'L1', 'inviteCode' => 'INV', 'keys' => ['1' => 'nk1', '3' => 'nk3']]];
$r1 = TableRematchView($m, $all, $T, 1)['result'];
check($r1['kind'] === 'room' && $r1['lobbyID'] === 'L1' && $r1['authKey'] === 'nk1', 'a returning seat gets ITS OWN new room key');
$r2 = TableRematchView($m, $all, $T, 2)['result'];
check(!isset($r2['authKey']) && ($r2['joinInvite'] ?? '') === 'INV', 'the leaver gets the invite to join as a new seat, never a key');
$m['tableRematch']['result'] = ['kind' => 'quick', 'gameName' => '555'];
check(TableRematchView($m, $all, $T, 3)['result'] === ['kind' => 'quick', 'gameName' => '555'], 'a quick result names the new game');

echo "PASS\n";
