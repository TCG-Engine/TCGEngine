<?php
// IC27_035
// Cost 3 - Yoda - A Disturbing Puzzle - [Vigilance,Heroism] - Unit (Ground) 3/4 (unique)
//   Traits: Force, Jedi, Republic
// Text: On Attack: You may choose an enemy unit. Prevent all combat damage that would be dealt to and dealt by that
//       unit for this phase.
//
// The choice is an optional MZMAYCHOOSE over ENEMY units (any arena, every opponent seat; in Team Suns the opposing
// team), the current defender included. The chosen unit gets the registered phase MARKER 'IC27_035', which
// SWUCombatDamage reads through _SWUIc27035Marked — the single-defender path and TWI_135 Maul's two-defender path:
//   * dealt BY it: its combat damage is zeroed, attacking (a unit or a base) or defending;
//   * dealt TO it: as an attacker it rides $preventAttackerDmg (TWI_096 Aayla's flag); as a defender the attacker's
//     damage to it is zeroed — so a marked defender is not defeated and gives no Overwhelm excess (prevented damage is
//     not excess: the official Shield/Overwhelm ruling).
// Unpreventable combat damage (ASH_196 Gorian Shard's Corsair) is not prevented. COMBAT damage only — ability damage
// is untouched (Aayla ruling: "combat damage" is only the damage dealt during the "deal combat damage" step).
// The marker is a lasting effect on the CHOSEN unit: it outlives Yoda and expires at the end of the phase.
$onAttackAbilities["IC27_035:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'IC27_035#0', 'side' => 'their', 'may' => true,
        'question' => "Choose_an_enemy_unit_to_prevent_combat_damage_to_and_by_it_this_phase?",
        'prompt' => "Choose_an_enemy_unit:_combat_damage_to_and_by_it_is_prevented_this_phase",
    ]);
};

$customDQHandlers["IC27_035#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    AddTurnEffect($lastDecision, 'IC27_035');
};

function _SWUIc27035Marked($obj): bool {
    if ($obj === null || !is_array($obj->TurnEffects ?? null)) return false;
    foreach ($obj->TurnEffects as $te) {
        if (SWUParseTurnEffect((string)$te)['base'] === 'IC27_035') return true;
    }
    return false;
}
