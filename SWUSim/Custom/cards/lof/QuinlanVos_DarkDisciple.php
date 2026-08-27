<?php
// LOF_163
// Cost 4 - Quinlan Vos - Dark Disciple - [Aggression] - Power 4 - HP 5
// Text: On Attack: If this unit has 6 or more power, you may deal 2 damage to an enemy base.

// LOF_163 Quinlan Vos — On Attack: if this unit has 6 or more power, may deal 2 to an enemy base.
$onAttackAbilities["LOF_163:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null || intval(ObjectCurrentPower($self)) < 6) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Deal_2_to_an_enemy_base?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_163#0", 1);
};

$customDQHandlers["LOF_163#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    // "AN enemy base" names no seat — the caster picks which. SWUQueueChooseOpponent auto-resolves to an
    // invisible PASSPARAMETER at one eligible opponent, so Premier is byte-identical.
    SWUQueueChooseOpponent(intval($player), 'LOF_163#BASE', "Deal_2_to_which_opponent's_base?");
};

$customDQHandlers["LOF_163#BASE"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp > 0) SWUDealDamageToBase(2, $opp);
};
