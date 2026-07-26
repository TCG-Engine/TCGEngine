<?php
// TWI_044
// Cost 2 - Kashyyyk Defender - [Vigilance,Heroism] - Power 0 - HP 5
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: Heal up to 2 damage from another unit and deal that much damage to this unit.

// TWI_044 Kashyyyk Defender — "Grit. When Played: Heal up to 2 damage from another unit and deal that
// much damage to this unit." (Mirrors SOR_075's heal-up-to-N-then-deal pattern.)
$whenPlayedAbilities["TWI_044:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? -1) === $selfUID) continue;      // "another unit"
            if (intval($o->Damage ?? 0) <= 0) continue;                  // must have damage to heal
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Heal_up_to_2_from_another_unit_(deal_that_much_to_this_unit)?", "Choose_a_unit_to_heal", "TWI_044#0|" . $selfUID);
};

$customDQHandlers["TWI_044#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cap = min(2, intval($o->Damage ?? 0));
    if ($cap <= 0) return;
    DecisionQueueController::AddDecision(intval($player), 'NUMBERCHOOSE', "0|{$cap}", 1, tooltip: 'Heal_how_much_(up_to_2)?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "TWI_044#1|{$lastDecision}|{$selfUID}", 1);
};

$customDQHandlers["TWI_044#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $targetMz = $parts[0] ?? '';
    $selfUID  = intval($parts[1] ?? 0);
    $o = GetZoneObject($targetMz);
    if (SWUObjGone($o)) return;
    $healed = max(0, min(intval($lastDecision), min(2, intval($o->Damage ?? 0))));
    if ($healed <= 0) return;
    OnHealUnit(intval($player), $targetMz, $healed);
    $selfMz = SWUFindMzByUID($selfUID);
    if ($selfMz !== null) SWUDealDamageToUnit($selfMz, $healed, intval($player)); // deal that much to this unit
};
