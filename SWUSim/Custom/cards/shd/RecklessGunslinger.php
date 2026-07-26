<?php
// SHD_160
// Cost 1 - Reckless Gunslinger - [Aggression] - Power 2 - HP 1
// Text: When Played: Deal 1 damage to each base. / Smuggle [3 resources Aggression] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_160 Reckless Gunslinger ──────────────────────────────────────────────
// When Played: Deal 1 damage to each base.
$whenPlayedAbilities["SHD_160:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(1, 1);
    SWUDealDamageToBase(1, 2);
};
