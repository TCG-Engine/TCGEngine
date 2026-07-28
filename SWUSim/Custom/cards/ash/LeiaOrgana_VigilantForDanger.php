<?php
// ASH_059
// Cost 3 - Leia Organa - Vigilant for Danger - [Vigilance,Heroism] - Power 3 - HP 4
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: You may deal 1 damage to this unit. If you do, heal 2 damage from your base.

// ASH_059 Leia Organa — On Attack: you may deal 1 damage to this unit; if you do, heal 2 damage from
// your base. ("this unit" = the attacker, own or Support-lent.)
$onAttackAbilities["ASH_059:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deal_1_to_this_unit_to_heal_2_from_your_base?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_059#0|{$uid}", 1);
};

$customDQHandlers["ASH_059#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    SWUDealDamageToUnit($mz, 1, intval($player));
    OnHealBase(intval($player), intval($player), 2);
};
