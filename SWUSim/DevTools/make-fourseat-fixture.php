<?php
// Seed a plain FOUR-SEAT Twin Suns game so the Home panel strip can be opened as each seat in turn.
//
// Built for the viewer-relative panel order (owner request 2026-09-26): each seat's strip must start at
// the seat to its RIGHT and wrap — P1 → P2 P3 P4, P2 → P3 P4 P1, P3 → P4 P1 P2, P4 → P1 P2 P3.
//
//   php SWUSim/DevTools/make-fourseat-fixture.php --game=9932001
//   php SWUSim/DevTools/make-fourseat-fixture.php --game=9932001 --remove
//
// ⚠ A FRESH ID PER BUILD. The web process caches a game as it was first created, so rewriting an
// existing id leaves the page serving the stale state.
//
// ⚠ Each seat gets a DIFFERENT, visually unmistakable leader, because the tile's leader art is how a
// screenshot says which seat a tile belongs to. With one leader across the table the strip order is
// unreadable in an image and the whole check would rest on the DOM alone.
//   P1 Thrawn (yk) · P2 Vader (rk) · P3 Luke (bw) · P4 Leia (gw)
// Leader codes are the 8 in CommonSetup.php's $leaderMap — {b,g,r,y} × {k,w}. Anything else throws.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();
chdir($repo);

$_gid = '9932001';
foreach ($argv as $a) if (str_starts_with($a, '--game=')) $_gid = substr($a, 7);
define('FIXTURE_GAME', $_gid);

if (in_array('--remove', $argv, true)) {
    $dir = "./SWUSim/Games/" . FIXTURE_GAME;
    if (is_dir($dir)) {
        foreach (glob("$dir/*") as $f) @unlink($f);
        @rmdir($dir);
        echo "removed " . FIXTURE_GAME . "\n";
    } else echo "nothing to remove\n";
    exit(0);
}

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

global $gameName, $playerID;
$gameName = FIXTURE_GAME;
$playerID = 1;

$schema = <<<'SCHEMA'
# FourSeatHomeStripFixture
## GIVEN
CommonSetup4P: yyk/rrk/bbw/ggw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SOR_128:1:0
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]
## WHEN
## EXPECT
SEATCOUNT:4
SCHEMA;

$res = SchemaTestRunner::runString($schema, 'fourseat-home-strip-fixture');
if (!$res->passed) { fwrite(STDERR, "fixture build FAILED: " . $res->message . "\n"); exit(1); }

@mkdir("./SWUSim/Games/" . FIXTURE_GAME, 0775, true);
WriteGamestate('./SWUSim/');

echo "created game " . FIXTURE_GAME . " (4 seats)\n";
foreach ([1, 2, 3, 4] as $s) {
    echo "  P{$s}: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=" . FIXTURE_GAME . "&playerID={$s}\n";
}
