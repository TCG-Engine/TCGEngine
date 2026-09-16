<?php
// HMW_130 Emerie Karr — Unit (Ground) 2/1, cost 1.
// "When Played: You may deal 1 damage to another ground unit. If you control that unit, the next unit you
//  play this phase costs 1 resource less."
// "another ground unit" = either side, not Emerie, in the ground arena. "you control" is read when the unit
// is chosen, before the damage (a unit the 1 damage defeats was still yours). The discount is a
// $oneShotPlayCharges row (GameLogic), cleared at the regroup.

$whenPlayedAbilities["HMW_130:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'HMW_130#0', 'arena' => 'Ground', 'excludeSelf' => true, 'may' => true,
        'question' => 'Deal_1_damage_to_another_ground_unit?', 'prompt' => 'Choose_a_ground_unit',
    ]);
};

$customDQHandlers["HMW_130#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) return;
    $yours = intval($o->Controller ?? 0) === intval($player);
    SWUDealDamageToUnit((string)$lastDecision, 1, intval($player));
    if ($yours) AddGlobalEffects(intval($player), 'SWU_HMW130_DISCOUNT_NEXT');
};
