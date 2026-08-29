<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './HellbreakSim/ZoneClasses.php';
include_once './HellbreakSim/ZoneAccessors.php';
include_once './HellbreakSim/GamestateParser.php';
include_once './HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

$menu = file_get_contents('./SharedUI/Sites/HellbreakSim/MainMenu.php');
$queue = file_get_contents('./APIs/Lobbies/JoinQueue.php');
$layout = file_get_contents('./HellbreakSim/Custom/GameLayout.php');
$client = file_get_contents('./HellbreakSim/Tutorial/tutorial-client.js');

// ⚠ ASSERT THE MECHANISM, NOT THE MARKETING COPY. This used to require the literal string
// 'Learn to Play'; the button was reworded to 'First Visit' / 'Shoreline orientation' and the test
// sat red while the feature worked perfectly. Copy is expected to change — the id, the handler and
// the lobby values are the contract, so pin those.
$check(str_contains($menu, 'id="start-tutorial-btn"') && str_contains($menu, 'startTutorial()'),
    'Hellbreak menu exposes a dedicated tutorial launch button wired to startTutorial()');
$check(str_contains($menu, "values.createTutorial = '1'") && str_contains($menu, "values.format = 'tutorial'"),
    'the tutorial launch requests the authored tutorial lobby mode');
// Same rewording: 'Solo Rules Test' / 'Player 2 automatically passes' became 'Solo Patrol'. What the
// check is really for is that the goldfish launch is a SEPARATE control from the tutorial one and
// still requests the goldfish format — assert that, which no rewrite of the label can break.
$check(str_contains($menu, 'id="start-fixture-match-btn"')
    && str_contains($menu, "values.createGoldfish = '1'")
    && str_contains($menu, "delete values.createGoldfish"),
    'the goldfish launch is a distinct control and the tutorial path clears its flag');
$check(str_contains($queue, "['AzukiSim', 'HellbreakSim']") && str_contains($queue, '$isHellbreakTutorial'),
    'the shared lobby creates Hellbreak tutorial games without changing Azukis mode');
$check(str_contains($layout, 'HellbreakTutorialIsActive')
    && str_contains($layout, '/HellbreakSim/Tutorial/tutorial-client.js'),
    'tutorial presentation assets load only for tutorial games');
$check(str_contains($client, 'Choose your location') && str_contains($client, 'Bid for initiative')
    && str_contains($client, 'Play Transylvanian Wolf') && str_contains($client, 'Attack with the Wolf')
    && str_contains($client, 'Enter Slumber'),
    'the lesson covers setup, bidding, card play, attack, schemes, and Slumber');
$check(str_contains($client, 'Tutorial Opponent · Auto-Pass'),
    'the lesson identifies the passive opponent explicitly');

InitializeGamestate();
DecisionQueueController::StoreVariable('GameMode', 'tutorial');
$adjusted = HellbreakTutorialAdjustResources(1, [
    'blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => ['Cursed' => 1],
]);
$check($adjusted['blood'] === 2 && $adjusted['malice'] === 1 && $adjusted['draw'] === 2,
    'the authored lesson grants the resources needed by the guided actions');
$check(count(HellbreakCardSchemeIcons('DOT_001')) === 2,
    'the lesson restores lurking Dracula scheme icons hidden by the minimal fixture');
HellbreakTutorialContinue(1);
$check(DecisionQueueController::GetVariable('TutorialIntroSeen') === '1',
    'completing the introduction persists across tutorial refreshes');

if($failures > 0) {
    fwrite(STDERR, PHP_EOL . "FAILED: {$failures} of {$checks} checks." . PHP_EOL);
    exit(1);
}
echo PHP_EOL . "ALL PASS ({$checks} checks)" . PHP_EOL;

?>
