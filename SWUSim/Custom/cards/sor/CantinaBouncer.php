<?php
// SOR_202
// Cost 5 - Cantina Bouncer - [Cunning,Cunning] - Power 3 - HP 5
// Text: When Played: You may return a non-leader unit to its owner's hand.

// SOR_202 Cantina Bouncer — When Played: You may return a non-leader unit to hand (either player).
$whenPlayedAbilities["SOR_202:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => !IsLeaderUnit($o)),
        'Return_a_non-leader_unit_to_hand?', 'Choose_a_non-leader_unit_to_return', 'BOUNCE_UNIT');
};
