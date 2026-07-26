<?php
// JTL_226
// Cost 7 - Radiant VII - Ambassadors' Arrival - [Cunning] - Power 5 - HP 6
// Text: Each enemy non-leader unit gets -1/-0 for each damage on it. / When Played: Deal 5 indirect damage to a player.

// ── JTL_226 Radiant VII — When Played: 5 indirect to a player (its -1/-0-per-damage aura is a passive). ─
$whenPlayedAbilities["JTL_226:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectToChosenPlayer(intval($player), 5, '', _SWUSrcUID($mzID));
};
