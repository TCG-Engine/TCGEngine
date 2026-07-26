<?php
// ASH_038
// Cost 8 - Purrgil Ultra - [Command,Cunning] - Power 6 - HP 10
// Text: When Played/When Defeated: You may return another friendly non-leader unit to its owner's hand. If you do, deal damage to a unit equal to the returned unit's cost.

// ASH_038 Purrgil Ultra — When Played/When Defeated: you may return ANOTHER friendly non-leader unit to
// its owner's hand. If you do, deal damage to a unit equal to the returned unit's cost.
$whenPlayedAbilities["ASH_038:0"] =
$whenDefeatedAbilities["ASH_038:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
            if (intval($o->UniqueID ?? -1) === $selfUID) continue;   // "another" unit
            $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Return_another_friendly_unit_to_hand_(then_deal_its_cost)?", "Choose_a_unit_to_return", "ASH_038#0");
};

$customDQHandlers["ASH_038#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cost = intval(CardCost($o->CardID ?? ''));
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;   // couldn't return → no damage
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_{$cost}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$cost}");
};
