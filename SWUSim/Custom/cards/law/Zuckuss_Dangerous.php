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
    if ($power <= 0) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $power, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_{$power}_to_a_ground_unit?", 'prompt' => "Choose_a_ground_unit",
    ]);
};
