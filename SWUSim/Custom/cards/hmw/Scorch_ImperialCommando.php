<?php
// HMW_064
// Cost 3 - Scorch, Imperial Commando - [Vigilance][Villainy] - Unit (Ground) 3/5
// Traits: Imperial, Clone, Trooper - Unique
// Text: On Attack: You may deal 1 damage to an upgraded unit.
//
// "an upgraded unit" carries no friendly/enemy qualifier, so ANY upgraded unit is legal — including
// Scorch himself when he is upgraded (side 'any', no excludeSelf).
// _SWUIsUpgraded counts any non-captive subcard, so a unit carrying only a TOKEN upgrade (Shield,
// Experience, Weakness) is "upgraded" — which is correct, tokens are upgrades.
// MZMAYCHOOSE (via 'may') is the safe pick inside OnAttack; a mandatory MZCHOOSE queued directly in an
// OnAttack closure auto-resolves to nothing. Combat owns the after-action.
$onAttackAbilities["HMW_064:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE|1',
        'side'         => 'any',
        'may'          => true,
        'extraFilter'  => fn($u) => _SWUIsUpgraded($u),
        'prompt'       => 'Deal_1_damage_to_an_upgraded_unit',
    ]);
};
