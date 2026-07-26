<?php
// LAW_076
// Cost 3 - Vult Skerris's Defender - Secret Project - [Aggression,Cunning,Villainy] - Power 3 - HP 3
// Text: When Played: If you discarded a card from your hand or deck this phase, give a Shield token to this unit. / On Attack: You may deal 1 damage to a space unit and exhaust it.

// LAW_076 Vult Skerris's Defender — When Played: if you discarded a card from hand/deck this phase,
// Shield this unit. On Attack: you may deal 1 to a space unit and exhaust it.
$whenPlayedAbilities["LAW_076:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_DISCARDED_PHASE') > 0) DoGiveShieldToken(intval($player), $mzID);
};

$onAttackAbilities["LAW_076:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $space = SWUAllUnits(null, SpaceArena);
    if (empty($space)) return;
    SWUQueueMayChooseTarget(intval($player), $space, "Deal_1_to_a_space_unit_and_exhaust_it?", "Choose_a_space_unit", "LAW_076#0");
};

$customDQHandlers["LAW_076#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = SWUFindMzByUID($uid);                          // re-resolve (damage may have shifted/defeated)
    if ($mz !== null) OnExhaustCard(intval($player), $mz);
};
