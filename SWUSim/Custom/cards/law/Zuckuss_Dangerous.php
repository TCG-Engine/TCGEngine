<?php
// LAW_064
// Cost 4 - Zuckuss - Dangerous - [Command,Cunning,Villainy] - Power 3 - HP 5
// Text: Saboteur / On Attack: If you control another Bounty Hunter unit, you may deal damage equal to this unit's power to a ground unit.

// LAW_064 Zuckuss — Saboteur + On Attack: if you control another Bounty Hunter unit, you may deal
// damage equal to this unit's power to a ground unit.
$onAttackAbilities["LAW_064:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? 0);
    $hasBH = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $uid) continue;
        if (HasTrait($u->CardID ?? '', 'Bounty Hunter')) { $hasBH = true; break; }
    }
    if (!$hasBH) return;
    $power = intval(ObjectCurrentPower($self));
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground) || $power <= 0) return;
    SWUQueueMayChooseTarget(intval($player), $ground, "Deal_{$power}_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|{$power}");
};
