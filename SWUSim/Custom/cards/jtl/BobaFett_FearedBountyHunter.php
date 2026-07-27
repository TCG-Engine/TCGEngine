<?php
// JTL_189
// Cost 5 - Boba Fett - Feared Bounty Hunter - [Cunning,Villainy] - Power 5 - HP 4 - Upgrade Power 2 - Upgrade HP 3
// Text: Shielded / Piloting [2 resources Cunning Villainy] / When played as an upgrade: You may deal 1 damage to a unit. If attached unit is a Transport, you may deal 2 damage instead.

// JTL_189 Boba Fett (pilot) — Shielded (keyword) + When played as an upgrade: You may deal 1 damage to a
// unit (2 instead if the attached unit is a Transport).
$whenPlayedAsUpgradeAbilities["JTL_189:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $amt = ($host !== null && HasTrait($host->CardID ?? '', 'Transport')) ? 2 : 1;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $amt, 'side' => 'any', 'may' => true,
        'question' => "Deal_{$amt}_damage_to_a_unit", 'prompt' => "Choose_a_unit",
    ]);
};
