<?php
// ── WithEliminatedSeats REFUSES the fixtures it cannot honour ───────────────────────────────────────
//
// The directive runs the engine's real SWUEliminateSeat() at the end of build(). Three of its four
// guards exist because the alternative is a SILENT no-op or a silently contradictory board — the exact
// failure mode the whole multi-seat sweep kept tripping over. The schema DSL has no way to say "expect
// a throw", so they are asserted here.
//     php -d xdebug.mode=off SWUSim/DevTools/tests/eliminated_seats_directive_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(realpath(__DIR__ . '/../../..'));
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
foreach (['./AccountFiles/AccountSessionAPI.php', './Core/HTTPLibraries.php', './Core/DeterministicRNG.php',
          './Core/CoreZoneModifiers.php', './Core/GameAuth.php', './SWUSim/ZoneClasses.php', './SWUSim/ZoneAccessors.php',
          './SWUSim/GeneratedCode/GeneratedCardDictionaries.php', './SWUSim/GamestateParser.php',
          './SWUSim/Tests/Framework/Assertions.php', './SWUSim/Tests/Framework/Cards.php',
          './SWUSim/Tests/Framework/CommonSetup.php', './SWUSim/Tests/Framework/GameStateBuilder.php',
          './SWUSim/Tests/Framework/GameTestAdapter.php', './SWUSim/Tests/Framework/SchemaTestRunner.php',
          './SWUSim/Tests/Framework/TestRunner.php'] as $f) include_once $f;

// Run a GIVEN block and return the exception message, or '' when it built cleanly.
function refusal(string $given): string {
    ob_start();
    try {
        SchemaTestRunner::runString("# Q\n## GIVEN\n{$given}\n## WHEN\n## EXPECT\nSEATCOUNT:3\n", 'probe');
        ob_end_clean();
        return '';
    } catch (Throwable $e) {
        ob_end_clean();
        return $e->getMessage();
    }
}

// 1. TWO SEATS. SWUEliminateSeat() early-returns at <= 2 seats, so without this guard the directive
//    would do NOTHING and the fixture would quietly assert a board that was never eliminated.
$m = refusal("CommonSetup: bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: 2");
check(str_contains($m, 'Twin Suns') || str_contains($m, 'SILENTLY IGNORED'),
      "two-seat board is refused rather than silently ignored (got: " . substr($m, 0, 60) . ")");

// 2. CONTRADICTION. WithLiveSeats states the live list; WithEliminatedSeats derives it. Accepting both
//    lets a fixture claim a list the elimination then contradicts.
$m = refusal("CommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: 3\nWithLiveSeats: 12");
check(str_contains($m, 'mutually exclusive'), "WithLiveSeats + WithEliminatedSeats together are refused");

// 3. FORMAT. A digit string like the sibling seat directives; anything else is a typo, and a typo that
//    parsed as "no seats" would be another silent no-op.
$m = refusal("CommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: P3");
check(str_contains($m, 'digit string'), "a non-digit spec ('P3') is refused, not read as zero seats");

// 4. TOO FAR. Below two live seats the game is already over, so the WHEN steps would run against a
//    finished game and every assertion after them would be meaningless.
$m = refusal("CommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: 23");
check(str_contains($m, 'fewer than 2 live seats'), "eliminating down to one live seat is refused");

// 5. ALREADY DEAD / NOT A SEAT. Eliminating a seat that is not live is a no-op inside the engine, so a
//    fixture naming the wrong seat would silently get an un-eliminated board.
$m = refusal("CommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: 4");
check(str_contains($m, 'not a live seat'), "naming a seat that is not live is refused");

// 6. THE HAPPY PATH still builds — a guard that refuses everything is not a guard.
$m = refusal("CommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithEliminatedSeats: 3");
check($m === '', "a well-formed 3-seat elimination builds cleanly (got: " . substr($m, 0, 60) . ")");

echo "PASS: eliminated_seats_directive_test\n";
