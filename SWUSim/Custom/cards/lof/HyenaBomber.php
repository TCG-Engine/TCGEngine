<?php
// LOF_158
// Cost 3 - Hyena Bomber - [Aggression] - Power 2 - HP 2
// Text: When Played: If you control another Aggression unit, you may deal 2 damage to a ground unit.

// LOF_158 Hyena Bomber — When Played: if you control another Aggression unit, may deal 2 to a ground unit.
$whenPlayedAbilities["LOF_158:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $hasAggr = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? -1) === $selfUID) continue;
        if (strpos(CardAspect($u->CardID ?? '') ?? '', 'Aggression') !== false) { $hasAggr = true; break; }
    }
    if (!$hasAggr) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_2_to_a_ground_unit?", 'prompt' => "Deal_2_damage_to_a_ground_unit",
    ]);
};
