<?php
// ── CardCurrentEffects READS THE CONTROLLER'S OWN GLOBALS, NOT "their" ──────────────────────────────
//
// It used to pick the zone with `$controller == $playerID ? "myGlobalEffects" : "theirGlobalEffects"`,
// and `their<Zone>` is the two-player idiom `$playerID == 1 ? 2 : 1`. So a seat-3 unit inspected from
// seat 1 read SEAT 2's global effects — a plausible-looking answer from the wrong board.
//
// ⚠ WHY THERE IS NO SCHEMA TEST FOR THIS. The bug is currently INERT and cannot be observed through
// the board: SWUGlobalEffectAttachesToUnit admits only non-SWU_ ids (none exist in SWUSim — 400 real
// gamestates checked, every global effect is SWU_-prefixed) or per-unit SWU_..._{uid} flags, and
// CardDisplayEffects then strips every SWU_ token before the badge renders. The raw CurrentEffects
// string is read by nothing. It is fixed as a LATENT landmine, so the guard has to call the function
// directly — which is also why this is worth a test rather than a comment: a comment cannot fail when
// the assumption behind it breaks.
//     php -d xdebug.mode=off SWUSim/DevTools/tests/cardcurrenteffects_seat_test.php
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

// A three-seat board with one unit on SEAT 3 — the seat OtherPlayer() can never name from seat 1.
ob_start();
SchemaTestRunner::runString(
    "# Q\n## GIVEN\nCommonSetup3P: bbk/bbk/bbk\nSkipPreGame: true\nWithActivePlayer: 1\n"
    . "WithP3GroundArena: SOR_046:1:0\n## WHEN\n## EXPECT\nSEATCOUNT:3\n", 'probe');
ob_end_clean();

global $playerID;
$playerID = 3;
$unit = GetGroundArena(3)[0] ?? null;
check($unit !== null, "seat 3 has the seeded unit");
$uid = intval($unit->UniqueID ?? 0);
check($uid > 0, "the unit has a real UniqueID ({$uid})");

// A per-unit flag on its OWN controller's globals — the shape SWUGlobalEffectAttachesToUnit admits.
AddGlobalEffects(3, 'SWU_ATTACKED_' . $uid);

// 1. THE BUG. Inspected from SEAT 1, the controller's zone must still be SEAT 3's. `their` would be
//    seat 2 here, whose globals are empty, so the flag would silently vanish.
$playerID = 1;
$fromSeat1 = explode(',', CardCurrentEffects($unit));
check(in_array('SWU_ATTACKED_' . $uid, $fromSeat1, true),
      "a seat-3 unit's own global effect is found when inspected from SEAT 1");

// 2. THE MIRROR that always worked — from the controller's own seat the `my` branch was already right.
//    Without this a fix that simply always read seat 3 would look correct too.
$playerID = 3;
$fromSeat3 = explode(',', CardCurrentEffects($unit));
check(in_array('SWU_ATTACKED_' . $uid, $fromSeat3, true),
      "the same effect is found when inspected from the controller's own seat");

// 3. THE NEGATIVE. A flag for THIS unit's UID sitting on a seat that does NOT control it must NOT be
//    picked up — otherwise "read every seat" would pass sections 1 and 2 while inventing effects.
$other = GetGroundArena(2);
AddGlobalEffects(2, 'SWU_CANT_READY_' . $uid);
$playerID = 1;
$again = explode(',', CardCurrentEffects($unit));
check(!in_array('SWU_CANT_READY_' . $uid, $again, true),
      "a same-UID flag on a NON-controlling seat is NOT applied");

echo "PASS: cardcurrenteffects_seat_test\n";
