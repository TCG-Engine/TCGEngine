<?php
// Twin Suns pass rule — CR §12.6.1.a.
//
//   "Players may NOT choose the Pass action available in other formats in place of taking a different
//    action. In the Twin Suns format, players may only pass if there are no counters available to take,
//    and must pass if they took a counter earlier in the round."
//
// §12.6.1.c then spells out the two consequences everyone quotes: at THREE seats the third player
// necessarily takes the last counter (so a Pass CHOICE never becomes legal at all), and at FOUR seats
// the last remaining player may pass once all three counters are gone. Those are not two separate
// rules — they fall out of the one above, which is why SWUPassActionAllowed() implements that and not
// a pair of seat-count special cases.
//
// Boots the real engine (same include chain as run-schema-tests.php) so these are true unit tests of
// the predicate rather than assertions about a fixture.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$repo = getenv('REPO_ROOT') ?: '/var/www/html/TCGEngine';
chdir($repo);

if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/GameAuth.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/Tests/Framework/Assertions.php';
include_once './SWUSim/Tests/Framework/Cards.php';
include_once './SWUSim/Tests/Framework/CommonSetup.php';
include_once './SWUSim/Tests/Framework/GameStateBuilder.php';
include_once './SWUSim/Tests/Framework/GameTestAdapter.php';
include_once './SWUSim/Tests/Framework/SchemaTestRunner.php';
include_once './SWUSim/Tests/Framework/TestRunner.php';
global $gameName, $playerID; $gameName = 'test_runner'; $playerID = 1;

$fails = 0;
function check($cond, $msg) { global $fails; echo ($cond ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$cond) $fails++; }

// Put the three counters in a named state. Initiative "_UNCLAIMED" is AVAILABLE to take;
// "_CLAIMED" means somebody already took it this round.
function counters(string $init, string $blast, string $plan): void {
    SetInitiativeCounter($init); SetBlastCounter($blast); SetPlanCounter($plan);
}

// ── TWO SEATS: the rule does not apply at all ────────────────────────────────────────────────────
// Premier has no blast/plan counters and keeps the ordinary Pass action. This is the regression guard
// for "don't let a Twin Suns rule leak into every other format".
InitializeGamestate();
SetSeatOrder('12'); SetLiveSeats('12');
counters('P1_UNCLAIMED', 'AVAILABLE', 'AVAILABLE');
check(SWUPassActionAllowed(1) === true, '2 seats: pass is always allowed (premier is untouched)');
check(SWUPassActionAllowed(2) === true, '2 seats: ...for either seat');
check(_SWUAnyCounterAvailable() === false, '2 seats: no Twin Suns counters exist to be available');

// ── THREE SEATS ──────────────────────────────────────────────────────────────────────────────────
InitializeGamestate();
SetSeatOrder('123'); SetLiveSeats('123');

counters('P1_UNCLAIMED', 'AVAILABLE', 'AVAILABLE');
check(_SWUAnyCounterAvailable() === true, '3 seats: all three counters available');
check(SWUPassActionAllowed(1) === false, '3 seats: cannot pass while all three counters are free');

// ⚠ THE CASE THE OLD CLIENT MISSED. It tested blast/plan only, so when the last free counter was the
// INITIATIVE — very common at three seats — Pass stayed offered exactly where the rules forbid it.
counters('P1_UNCLAIMED', 'P2', 'P3');
check(_SWUAnyCounterAvailable() === true, '3 seats: an unclaimed INITIATIVE still counts as available');
check(SWUPassActionAllowed(1) === false, '3 seats: cannot pass when only the initiative is left');

counters('P1_CLAIMED', 'P2', 'AVAILABLE');
check(SWUPassActionAllowed(3) === false, '3 seats: cannot pass when only the plan counter is left');

counters('P1_CLAIMED', 'P2', 'P3');
check(_SWUAnyCounterAvailable() === false, '3 seats: every counter taken');
check(SWUPassActionAllowed(1) === true,  '3 seats: pass allowed once every counter is taken');

// The FORCED pass is never blocked: a seat that took a counter must pass for the rest of the round,
// and SWUSwapTurnPlayer auto-passes it. Blocking that would deadlock the phase.
counters('P1_UNCLAIMED', 'AVAILABLE', 'AVAILABLE');
SetSWUVar('SWU_COUNTER_TAKEN', '2');
check(_SWUSeatTookCounterThisRound(2) === true, '3 seats: seat 2 is recorded as having taken a counter');
check(SWUPassActionAllowed(2) === true,  '3 seats: a seat that TOOK a counter may still pass (forced)');
check(SWUPassActionAllowed(1) === false, '3 seats: ...while a seat that has not still cannot');
SetSWUVar('SWU_COUNTER_TAKEN', '');

// ── FOUR SEATS: the reported case — Pass appears only after all three are gone ────────────────────
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');

counters('P1_CLAIMED', 'P2', 'AVAILABLE');
check(SWUPassActionAllowed(4) === false, '4 seats: the 4th seat cannot pass while the plan counter is free');

counters('P1_CLAIMED', 'P2', 'P3');
check(SWUPassActionAllowed(4) === true,  '4 seats: the 4th seat MAY pass once all three are taken');

// ── ELIMINATION RE-OPENS A COUNTER ───────────────────────────────────────────────────────────────
// The case neither "3P: never pass" nor "4P: pass after three" describes on its own, and the reason
// the general rule is implemented instead of those two. SWUEliminateSeat() hands a dead seat's blast
// and plan back to the centre, so a four-player game that drops to three can have a counter free up
// again mid-round — and passing has to become illegal again.
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234');
counters('P1_CLAIMED', 'P2', 'P3');
check(SWUPassActionAllowed(4) === true, 'before elimination: all counters taken, seat 4 may pass');
SetLiveSeats('134');
SetBlastCounter('AVAILABLE');                  // what SWUEliminateSeat does with seat 2's blast counter
check(_SWUAnyCounterAvailable() === true, 'after elimination: the dead seat\'s blast counter is free again');
check(SWUPassActionAllowed(4) === false, 'after elimination: seat 4 must take the freed counter, not pass');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
