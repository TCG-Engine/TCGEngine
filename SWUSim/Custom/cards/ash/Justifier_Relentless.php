<?php
// ASH_146
// Cost 5 - Justifier - Relentless - [Aggression,Villainy] - Power 4 - HP 5
// Text: When Played/On Attack: You may deal 1 damage to a unit. If that unit is defeated this way, give an Advantage token to a unit.

// ASH_146 Justifier — When Played/On Attack: you may deal 1 damage to a unit; if it's defeated this way,
// give an Advantage token to a unit.
$whenPlayedAbilities["ASH_146:0"] = $onAttackAbilities["ASH_146:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_to_a_unit_(Advantage_if_it_dies)?", "Choose_a_unit", "ASH_146#0");
};

$customDQHandlers["ASH_146#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    // if that unit was defeated this way → give an Advantage token to a unit
    if (SWUFindMzByUID($uid) === null) {
        GiveTokenUpgrade($player, '', [
            'token' => 'ADVANTAGE', 'friendlyOnly' => false,
            'prompt' => "Give_an_Advantage_token_to_a_unit",
        ]);
    }
};
