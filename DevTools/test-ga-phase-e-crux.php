<?php
// Retained loaded-state checks for Phase E Crux clauses that scenario play cannot reach cheaply:
// Vorpal Sword's [Merlin Bonus] [REST], banish-a-material-card ability must be unavailable without the bonus, while rested, or with no material card to banish
// (a direct action bypasses prereqs, so gameplay fixtures can only show the positive path).
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_crux_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function cxCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function cxLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/slipstream-target-cost-ordinary/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $vorpal = 'PIcB5KuuMd';
    $vassal = '6R8XmWoKLn'; // Merlin, Memorite Vassal: a Merlin lineage card
    $setup = function (bool $merlin, int $status, bool $material) use ($vorpal, $vassal) {
        cxLoad();
        $champ = &GetField(1)[0];
        $champ->Subcards = $merlin ? [$vassal] : [];
        $sword = AddField(1, CardID: $vorpal, Owner: 1, Controller: 1);
        $sword->Status = $status;
        if (!$material) { $mat = &GetMaterial(1); $mat = []; }
        return count(GetField(1)) - 1;
    };
    $idx = $setup(true, 2, true);
    cxCheck(CanActivateAbility(1, "myField-$idx", 0) === true, 'Vorpal Sword: with the Merlin Bonus, awake, and a material card, the ability can be activated');
    $idx = $setup(false, 2, true);
    cxCheck(CanActivateAbility(1, "myField-$idx", 0) === false, 'Vorpal Sword: without the Merlin Bonus the ability cannot be activated');
    $idx = $setup(true, 1, true);
    cxCheck(CanActivateAbility(1, "myField-$idx", 0) === false, 'Vorpal Sword: a rested Sword cannot pay [REST]');
    $idx = $setup(true, 2, false);
    cxCheck(CanActivateAbility(1, "myField-$idx", 0) === false, 'Vorpal Sword: with no material card to banish the ability cannot be activated');
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
