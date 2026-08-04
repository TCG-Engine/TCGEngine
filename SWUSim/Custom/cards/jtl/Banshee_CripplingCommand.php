<?php
// JTL_037
// Cost 5 - Banshee - Crippling Command - [Vigilance,Villainy] - Power 4 - HP 5
// Text: On Attack: You may deal damage to a unit equal to the amount of damage on this unit.

// ── JTL_037 Banshee — On Attack: You may deal damage to a unit equal to the damage on this unit. ─────
$onAttackAbilities["JTL_037:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $amount = intval($self->Damage ?? 0);
    if ($amount <= 0) return; // no damage on this unit → nothing to deal
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_damage_on_this_unit", "Deal_{$amount}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};
