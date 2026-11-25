<?php
// Opponent-selected targets must resolve in the attacker's coordinate system.
chdir(dirname(__DIR__, 2));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_tweedledum_target_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function ttCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
}
try {
    foreach ([1, 2] as $attackerPlayer) {
        $playerID = 1;
        copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
        ParseGamestate('./GrandArchiveSim/');
        foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
        $playerID = $attackerPlayer;
        $attacker = AddField($attackerPlayer, CardID: 'UmZpK4rt2M', Status: 2, Owner: $attackerPlayer, Controller: $attackerPlayer);
        $attackerMZ = 'myField-' . $attacker->mzIndex;
        // Different indexes and distinct identities expose accidental perspective swaps.
        $defender = 3 - $attackerPlayer;
        $target = AddField($defender, CardID: '7w4v1hgl3e', Status: 1, Owner: $defender, Controller: $defender);
        $targetMZ = 'theirField-' . $target->mzIndex;
        StoreCombatAttackerState($attackerMZ, $attackerPlayer);
        ChooseAttackTarget($attackerPlayer, $attackerMZ);
        $q = &GetDecisionQueue($defender);
        ttCheck(in_array(FlipZonePerspective($targetMZ), explode('&', $q[0]->Param)), 'Opponent can choose the intended defender');
        $q = [];
        $playerID = $defender;
        $customDQHandlers['TweedledumTargetChosen']($defender, [$attackerMZ, strval($attackerPlayer)], FlipZonePerspective($targetMZ));
        ttCheck($playerID === $defender, 'Ambient perspective restored');
        ttCheck(DecisionQueueController::GetVariable('CombatTarget') === $targetMZ, 'Chosen target stored in attacker perspective');
        ttCheck(intval(DecisionQueueController::GetVariable('CombatTargetUniqueID')) === intval($target->UniqueID), 'Combat targets the selected object');
        foreach ([1, 2] as $seat) foreach (GetDecisionQueue($seat) as $d) {
            ttCheck(!str_starts_with($d->Param ?? '', 'TweedledumTargetChosen'), 'No target-choice loop');
        }
    }
    echo "OK $checks checks\n";
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
