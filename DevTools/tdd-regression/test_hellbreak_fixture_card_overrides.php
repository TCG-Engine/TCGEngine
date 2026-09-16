<?php

// The tutorial's simplified card values must stay in the tutorial.
//
// 2026-09-15: HellbreakFixtureCards() hardcodes teaching values for 14 cards (both starter monsters
// among them) and HellbreakFixtureCard() applied them to EVERY game: QuickStartFixtures.php is
// always loaded by GameLogic.php, and the FixtureMode flag CreateGame.php writes was never read.
// So a real game fed Dracula's tutorial numbers (0 blood, 1 malice) instead of his printed resource
// bar (2 blood, 2 draw), and no card could be played on round 1 for want of blood.
//
// Reviewed card data must keep flowing in BOTH modes: HellbreakReviewedCard() is how
// ReviewedCardFaces.json reaches the engine at runtime.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// The dictionary is generated, so stub the two readers this test needs when it is absent. The
// values mirror DOT_001/DOT_025 as printed, which is exactly what the engine should be using.
if(!function_exists('CardResources')) {
    function CardResources($cardID) {
        $data = [
            'DOT_001' => '{"blood":2,"malice":0,"draw":2,"aspects":{"Cursed":1}}',
            'DOT_006' => '{"blood":2,"malice":0,"draw":2,"aspects":{"Feral":1}}',
            'DOT_025' => '{"blood":1,"malice":1,"draw":0,"aspects":{"Cursed":1}}',
        ];
        return $data[$cardID] ?? '';
    }
}
if(!function_exists('CardType')) { function CardType($cardID) { return 'Minion'; } }

include_once './Core/DecisionQueueController.php';
include_once './HellbreakSim/GamestateParser.php';
include_once './HellbreakSim/ZoneAccessors.php';
include_once './HellbreakSim/ZoneClasses.php';
include_once './HellbreakSim/Custom/GameLogic.php';
InitializeGamestate();   // DecisionQueueController variables live in the gamestate

$setTutorial = function(bool $active): void {
    DecisionQueueController::StoreVariable('GameMode', $active ? 'tutorial' : '');
};

// ---------------------------------------------------------------------------
// A normal game uses the printed card
// ---------------------------------------------------------------------------
$setTutorial(false);
$dracula = HellbreakCardResources('DOT_001');
$check($dracula['blood'] === 2 && $dracula['draw'] === 2 && $dracula['malice'] === 0,
    'outside the tutorial Dracula feeds his printed 2 blood + 2 draw');
$check(($dracula['aspects']['Cursed'] ?? 0) === 1, 'his Cursed aspect icon survives');
$jaws = HellbreakCardResources('DOT_006');
$check($jaws['blood'] === 2 && $jaws['draw'] === 2, 'outside the tutorial Jaws feeds his printed 2 blood + 2 draw');

// ---------------------------------------------------------------------------
// The tutorial still teaches with its own numbers
// ---------------------------------------------------------------------------
$setTutorial(true);
$draculaTutorial = HellbreakCardResources('DOT_001');
$check($draculaTutorial['blood'] === 0 && $draculaTutorial['malice'] === 1,
    'inside the tutorial Dracula still feeds the lesson values (0 blood, 1 malice)');
$check(HellbreakFixtureCard('DOT_001') !== null, 'the tutorial still gets a fixture card for DOT_001');

// ---------------------------------------------------------------------------
// Reviewed data reaches the engine in both modes
// ---------------------------------------------------------------------------
foreach([false, true] as $tutorial) {
    $setTutorial($tutorial);
    $label = $tutorial ? 'tutorial' : 'normal game';
    $bat = HellbreakCardResources('DOT_025');
    $check($bat['blood'] === 1 && $bat['malice'] === 1, "a card with no fixture entry keeps its reviewed resources ({$label})");
    $reviewed = HellbreakFixtureCard('DOT_025');
    $check(is_array($reviewed) && ($reviewed['text'] ?? '') !== '', "reviewed card data still reaches the engine ({$label})");
}
$setTutorial(false);

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
