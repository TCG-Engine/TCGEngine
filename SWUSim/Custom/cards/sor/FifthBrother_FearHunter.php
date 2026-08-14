<?php
// SOR_131
// Cost 3 - Fifth Brother - Fear Hunter - [Aggression,Villainy] - Power 2 - HP 4
// Text: This unit gains Raid 1 for each damage on him. / On Attack: You may deal 1 damage to this
// unit and 1 damage to another ground unit.
// (The Raid-per-damage half lives in GetConditionalKeyword_Raid_Value, KeywordEffects.php. This file
// adds the On Attack, which had a generated stub but NO handler — the ability silently no-oped since
// the set was built.)

// On Attack: optional self-ping + ping another ground unit (either side). No other ground unit → no
// offer (the compound effect can't resolve; SWUOfferUnitTarget's empty-pool return handles it). The
// self-damage lands BEFORE combat damage, so it feeds his Raid-per-damage on this same attack — and a
// lethal self-ping (1 remaining HP) defeats him mid-attack, cancelling his combat damage.
$onAttackAbilities["SOR_131:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => "SOR_131#0|" . intval($self->UniqueID ?? 0),
        'may' => true, 'arena' => 'Ground', 'side' => 'any', 'excludeSelf' => true,
        'question' => "Deal_1_to_Fifth_Brother_and_1_to_another_ground_unit?",
        'prompt'   => "Choose_another_ground_unit",
    ]);
};

$customDQHandlers["SOR_131#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    $selfMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($selfMz !== null) SWUDealDamageToUnit($selfMz, 1, intval($player));   // self first (printed order)
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};
