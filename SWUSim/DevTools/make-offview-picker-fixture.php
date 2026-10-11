<?php
// Seed a 3-seat Twin Suns game parked on a decision whose TARGETS BELONG TO AN OFF-VIEW SEAT, so the
// board can actually be opened in that state (bug #1086, game 1310526).
//
// TS26_80 Reveal Intentions: "In player order, each player discards a card from the hand of the player
// to their right." At three seats seat 2's right neighbour is seat 3 — a seat that is NOT seat 2's
// in-view opponent — and the reported symptom is that seat 2 can never make that pick, which stalls the
// whole walk (no further discards, and no closing draw for anybody).
//
// The engine is not the problem: the schema suite resolves this walk correctly at 3 and 4 seats, and
// seat 2 really does hold the decision with a p3Hand-* pool. What cannot be checked headlessly is
// whether seat 2's BOARD gives them anything to click, which is what this fixture exists to show.
//
//   php SWUSim/DevTools/make-offview-picker-fixture.php            # create (prints the URLs)
//   php SWUSim/DevTools/make-offview-picker-fixture.php --remove   # delete it again
//
// ⚠ Open it as playerID=2 — that is the seat holding the stuck decision. playerID=1 is the control:
//   seat 1's right neighbour IS its in-view opponent, and that pick worked in the live game.
// ⚠ Sacrificial game id, far outside the real counter's range.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();
chdir($repo);

// ⚠ A FRESH ID PER BUILD, always. The web process caches a game as it was first created, so rewriting
// an existing id leaves the page serving the stale state — which shows up as a picker that is already
// open before the caster has answered. Pass --game=<id> and never reuse one within a run.
$_gid = '9931086';
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

// Animation stubs + engine/framework chain, identical to the schema runner's.
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

// Distinct cards per seat so a screenshot says WHOSE hand is on screen, and units on every board so the
// home tiles have something to draw (an empty tile hides whether the hand is rendered at all).
$schema = <<<'SCHEMA'
# OffViewPickerFixture
## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024:0
WithP1Hand: [TS26_80 SOR_095 SOR_128]
WithP2Hand: [SOR_046 SEC_080]
WithP3Hand: [LOF_070 TWI_067 ASH_083]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SEC_080:1:0
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
SCHEMA;

// --answered parks the game one step later, with the caster's pick already made and seat 2 holding the
// decision. That is the state a FRESH page load lands in; the bug report is about an already-open page,
// so the default stops BEFORE the answer and lets the harness drive it through the real endpoint.
if (in_array('--answered', $argv, true)) {
    $schema = str_replace("- P1>PlayHand:0\n## EXPECT\nP1HASDECISION",
                          "- P1>PlayHand:0\n- P1>AnswerDecision:p2Hand-0\n## EXPECT\nP2HASDECISION", $schema);
}

$res = SchemaTestRunner::runString($schema, 'offview-picker-fixture');
if (!$res->passed) { fwrite(STDERR, "fixture build FAILED: " . $res->message . "\n"); exit(1); }

// Report who holds what, so the browser check knows which seat to watch and what to look for.
foreach ([1, 2, 3] as $s) {
    $param = '';
    foreach (GetDecisionQueue($s) as $d) { if (empty($d->removed)) { $param = (string)($d->Param ?? ''); break; } }
    echo "seat {$s} pending decision param: " . ($param === '' ? '(none)' : $param) . "\n";
}

@mkdir("./SWUSim/Games/" . FIXTURE_GAME, 0775, true);
WriteGamestate('./SWUSim/');

echo "created game " . FIXTURE_GAME . "\n";
echo "  STUCK SEAT : http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=" . FIXTURE_GAME . "&playerID=2\n";
echo "  CONTROL    : http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=" . FIXTURE_GAME . "&playerID=1\n";
