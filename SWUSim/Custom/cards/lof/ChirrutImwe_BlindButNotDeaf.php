<?php
// LOF_067
// Cost 4 - Chirrut Îmwe - Blind, but not Deaf - [Vigilance] - Power 3 - HP 5
// Text: Sentinel / When this unit is attacked (before damage is dealt): You may use the Force (lose your Force token). If you do, the attacker gets -2/-0 for this attack.

$onDefenseAbilities["LOF_067:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_give_the_attacker_-2/-0?", "LOF_067#0");
};

$customDQHandlers["LOF_067#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $atk = GetSWUVar('SWU_CURRENT_ATTACKER', '');
    if ($atk === '' || $atk === null) return;
    $atkDef = preg_replace('/^my/', 'their', $atk);
    AddTurnEffect($atkDef, SWUMakeTurnEffect('SWUDEBUFF', [2, 0], SWU_DUR_ATTACK));
};

$customDQHandlers["LOF_067#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $atk = GetSWUVar('SWU_CURRENT_ATTACKER', '');
    if ($atk === '' || $atk === null) return;
    $atkDef = preg_replace('/^my/', 'their', $atk); // attacker stored in attacker frame; flip to defender frame
    AddTurnEffect($atkDef, SWUMakeTurnEffect('SWUDEBUFF', [2, 0], SWU_DUR_ATTACK));
};
