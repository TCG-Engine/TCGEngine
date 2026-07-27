<?php
// SEC_241
// Cost 3 - Political Bully - [Villainy] - Power 2 - HP 3
// Text: When Played: If you control another Official unit, you may deal 2 damage to a ground unit.

// SEC_241 Political Bully — When Played: if you control another Official unit, you may deal 2 to a ground unit.
$whenPlayedAbilities["SEC_241:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hasOfficial = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $selfUID) continue;
        if (HasTrait($u->CardID ?? '', 'Official')) { $hasOfficial = true; break; }
    }
    if (!$hasOfficial) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_2_to_a_ground_unit?", 'prompt' => "Choose_a_ground_unit",
    ]);
};
