<?php
// LAW_062
// Cost 6 - Defiant Hammerhead - [Command,Aggression] - Power 6 - HP 6
// Text: On Attack: If this unit is attacking a unit, you may give this unit +4/+0 for this attack. If you do, defeat this unit after completing this attack.

// LAW_062 Defiant Hammerhead — On Attack: if attacking a unit, you may give this unit +4/+0 for this
// attack. If you do, defeat this unit after completing the attack.
$onAttackAbilities["LAW_062:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $def = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($def === '' || strpos($def, 'Arena') === false) return;   // not attacking a unit (base attack)
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Give_this_unit_+4/+0_(then_defeat_it_after_the_attack)?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_062#0|{$uid}", 1);
};

$customDQHandlers["LAW_062#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    SWUAddAttackPowerBonus($mz, 4);
    AddTurnEffect($mz, SWUMakeTurnEffect('LAW_062', [], SWU_DUR_ATTACK));   // self-defeat after the attack
};
