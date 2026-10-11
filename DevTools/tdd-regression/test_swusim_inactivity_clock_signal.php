<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_inactivity_clock_signal.php
//
// Inactivity clock stage 1 — WHAT STAMPS THE CLOCK, end to end through the real endpoints.
// This is the file that pins the user's rules (2026-09-17): a no-op must not reset the clock, an attack
// DECLARATION must not, choosing the target must, a PASS decline must not, chat must not, and a poll must
// record a heartbeat (spectator polls must not).
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

// Presence lives in the WEB server's APCu, which this CLI process cannot see — so read it back over HTTP.
function clock_dump(string $gn): array
{
    $j = json_decode(swuchat_http('SWUSim/DevTools/zz_presence_dump.php?gameName=' . urlencode($gn)), true);
    return is_array($j) ? $j : [];
}
function acted_at(string $gn, int $seat): int { return intval(clock_dump($gn)['acted'][$seat] ?? 0); }
function seen_at(string $gn, int $seat): int  { return intval(clock_dump($gn)['seen'][$seat] ?? 0); }
function submit(string $gn, string $pid, int $mode, string $cardID = '', array $extra = []): string
{
    $q = array_merge(['gameName' => $gn, 'playerID' => $pid, 'authKey' => 'testschema',
                      'folderPath' => 'SWUSim', 'mode' => $mode, 'cardID' => $cardID], $extra);
    return trim(swuchat_http('ProcessInput.php?' . http_build_query($q)));
}

$checks = [];

// A 2-seat board where seat 1 has a ready unit, an exhausted unit and a playable card.
$SCHEMA = <<<'MD'
## GIVEN
CommonSetup: bbw/rrk/{myResources:8; theirResources:8}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0 SOR_033:0:0]
WithP2GroundArena: [SOR_034:1:0]
WithP1Hand: [SOR_046]

## WHEN

## EXPECT
TURNPLAYER:1
MD;

$gn = swuchat_make_game($SCHEMA);
$checks['fixture created'] = $gn !== '';
// The clock is off in dev by default — opt this game in (see swuchat_clock_on).
$checks['clock enabled for this game'] = swuchat_clock_on($gn);

// ── the poll is the heartbeat ──
swuchat_poll($gn, '1');
$checks['seat poll stamps seen']        = seen_at($gn, 1) > 0;
$checks['other seat not stamped yet']   = seen_at($gn, 2) === 0;
swuchat_poll($gn, 'S');
$checks['spectator poll stamps nobody'] = seen_at($gn, 2) === 0;

// ── a no-op must NOT stamp ──
$before = acted_at($gn, 1);
submit($gn, '1', 10002, 'myGroundArena-1!FSM!');        // SOR_033 is EXHAUSTED -> refusal
$checks['no-op (exhausted unit) does not stamp'] = acted_at($gn, 1) === $before;
submit($gn, '2', 10002, 'myGroundArena-0!FSM!');        // not seat 2's turn -> refusal
$checks['out-of-turn click does not stamp']      = acted_at($gn, 2) === 0;

// ── chat must NOT stamp ──
$checks['chat send OK']        = swuchat_send($gn, '1', 'hello') === 'OK';
$checks['chat does not stamp'] = acted_at($gn, 1) === $before;

// ── an attack DECLARATION must not stamp; choosing the target must ──
submit($gn, '1', 10002, 'myGroundArena-0!FSM!');        // declare with SOR_032 -> target prompt opens
$checks['attack declaration does not stamp'] = acted_at($gn, 1) === $before;
// PASS at the target prompt hands the action back -> must NOT stamp (anti-stall rule).
submit($gn, '1', 100, 'PASS', ['buttonInput' => 'PASS']);
$checks['PASS decline does not stamp']       = acted_at($gn, 1) === $before;
// Declare again and actually choose a target -> MUST stamp.
submit($gn, '1', 10002, 'myGroundArena-0!FSM!');
submit($gn, '1', 100, 'theirGroundArena-0');
$checks['choosing the attack target stamps'] = acted_at($gn, 1) > $before;

// ── an ordinary state-changing action stamps ──
$gn2 = swuchat_make_game($SCHEMA);
swuchat_clock_on($gn2);
$b2 = acted_at($gn2, 1);
submit($gn2, '1', 10002, 'myHand-0!FSM!');              // play a card
$checks['playing a card stamps'] = acted_at($gn2, 1) > $b2;

// Pass stamps too (it is how a turn legitimately moves on).
$gn3 = swuchat_make_game($SCHEMA);
swuchat_clock_on($gn3);
submit($gn3, '1', 10001, 'myHealth-0!CustomInput!');
$checks['pass stamps'] = acted_at($gn3, 1) > 0;

// ── a local mode never stamps ──
$GOLD = str_replace('WithActivePlayer: 1', "WithActivePlayer: 1\nWithP1GlobalEffect: SWU_MODE_GOLDFISH", $SCHEMA);
$gg = swuchat_make_game($GOLD);
swuchat_clock_on($gg);   // even opted in, goldfish must never stamp
submit($gg, '1', 10002, 'myHand-0!FSM!');
$checks['goldfish never stamps'] = acted_at($gg, 1) === 0;

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "games: $gn $gn2 $gn3 $gg\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
