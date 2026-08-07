<?php
// ASH_216
// Cost 2 - Mandalorian Scout - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Exhaust a ready friendly resource.

// ASH_216 Mandalorian Scout — When Defeated: exhaust a ready friendly resource.
$whenDefeatedAbilities["ASH_216:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // EFFECT, not a cost — "exhaust a ready friendly resource" is a downside this unit inflicts on its
    // own controller, so it must stay a plain resource exhaust. A Credit token is never spent for it
    // (CR 3.13 only lets a Credit substitute while PAYING resources).
    SWUExhaustResources(intval($player), 1, true);
};
