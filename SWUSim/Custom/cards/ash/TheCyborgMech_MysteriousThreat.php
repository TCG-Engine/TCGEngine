<?php
// ASH_147
// Cost 6 - The Cyborg Mech - Mysterious Threat - [Aggression,Villainy] - Power 3 - HP 7
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: Either deal 2 damage to an undamaged ground unit or 5 damage to a damaged ground unit.

// ASH_147 The Cyborg Mech — Grit (keyword) + When Played: deal 2 to an UNDAMAGED ground unit OR 5 to a
// DAMAGED ground unit. The amount is determined by the chosen target's damage.
$whenPlayedAbilities["ASH_147:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits(null, GroundArena);
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_5_to_a_damaged_or_2_to_an_undamaged_ground_unit", "ASH_147#0");
};

$customDQHandlers["ASH_147#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $amt = intval($o->Damage ?? 0) > 0 ? 5 : 2;
    SWUDealDamageToUnit($lastDecision, $amt, intval($player));
};
