<?php
// TWI_151
// Cost 10 - Resolute - Under Anakin's Command - [Aggression,Heroism] - Power 8 - HP 8
// Text: This unit costs 1 resource less to play for every 5 damage on your base. / When Played/On Attack: Deal 2 damage to an enemy unit and each other enemy unit with the same name as that unit.

// TWI_151 Resolute — "When Played/On Attack: Deal 2 damage to an enemy unit and each other enemy unit
// with the same name as that unit." (Cost reduction is a $playCostModifier.)
$whenPlayedAbilities["TWI_151:0"] = $onAttackAbilities["TWI_151:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_an_enemy_unit_and_each_same-named_enemy_unit", "TWI_151#0");
};

$customDQHandlers["TWI_151#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    $name = SWUObjectTitle($chosen);
    // Snapshot every enemy unit sharing the chosen unit's name (incl. the chosen one) by UID.
    $uids = [];
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && SWUObjectTitle($o) === $name) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
