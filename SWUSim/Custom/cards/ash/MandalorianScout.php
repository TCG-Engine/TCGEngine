<?php
// ASH_216
// Cost 2 - Mandalorian Scout - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Exhaust a ready friendly resource.

// ASH_216 Mandalorian Scout — When Defeated: exhaust a ready friendly resource.
$whenDefeatedAbilities["ASH_216:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUExhaustResources(intval($player), 1);   // exhaust 1 ready resource
};
