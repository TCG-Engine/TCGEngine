<?php
// Retained loaded-state checks for Phase E Exalted clauses that scenario play cannot easily vary:
//  - Labyrinth, Jeweled Opus's [Sheen 10+] spellshroud/stealth needs an AWAKE champion, sheen 10+ and an opponent whose memory holds six or fewer cards.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_exalted_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function exCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function exLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $labyrinth = 'A58xZJJMz6'; $guide = 'em6eEh9q8y';
    $case = function (int $sheen, int $status, int $oppMemory, bool $withLabyrinth = true) use ($labyrinth, $guide) {
        exLoad();
        if ($withLabyrinth) AddField(1, CardID: $labyrinth, Owner: 1, Controller: 1);
        $fm = GetFracturedMemoriesMasteryObject(1);
        if ($fm === null) { AddMastery(1, 'UAJGQFbXjs'); $fm = GetFracturedMemoriesMasteryObject(1); }
        $fm->Counters = ['sheen' => $sheen];
        GetPlayerChampion(1)->Status = $status;
        for ($i = 0; $i < $oppMemory; ++$i) AddMemory(2, $guide);
        return LabyrinthJeweledOpusPassiveActive(1);
    };
    exCheck($case(10, 2, 6) === true, 'Labyrinth: sheen 10, awake champion, opponent memory 6 -> active');
    exCheck($case(9, 2, 0) === false, 'Labyrinth: sheen 9 -> inactive');
    exCheck($case(10, 1, 0) === false, 'Labyrinth: a rested champion loses it');
    exCheck($case(10, 2, 7) === false, 'Labyrinth: an opponent with seven cards in memory switches it off');
    exCheck($case(10, 2, 0, false) === false, 'Labyrinth: without the Labyrinth on the field -> inactive');
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
