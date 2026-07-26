<?php
// ASH_196
// Cost 6 - Gorian Shard's Corsair - Pirate Warship - [Cunning,Villainy] - Power 6 - HP 5
// Text: Damage dealt by friendly Underworld cards is unpreventable. / When Played/On Attack: You may deal 2 damage to a unit.

// ASH_196 Gorian Shard's Corsair — When Played/On Attack: you may deal 2 damage to a unit. Gorian is an
// Underworld card, so his own "Damage dealt by friendly Underworld cards is unpreventable" passive applies
// to THIS damage — thread his own UniqueID as the source so SWUDealDamageToUnit bypasses Shields/prevention.
$whenPlayedAbilities["ASH_196:0"] = $onAttackAbilities["ASH_196:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    $self = GetZoneObject($mzID);
    $srcUID = SWUObjUID($self, 0);
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_2_damage_to_a_unit?", "Choose_a_unit", "ASH_196#DMG|{$srcUID}");
};

// Deal Gorian's 2, threading his own mzID (resolved by UID) as the source so the Underworld-unpreventable
// passive is honored (SWUDealDamageToUnit checks _SWUDamageUnpreventable on the threaded source).
$customDQHandlers["ASH_196#DMG"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    $srcMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    SWUDealDamageToUnit($lastDecision, 2, intval($player), $srcMz);
};
