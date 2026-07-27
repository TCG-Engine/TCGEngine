<?php
// SOR_209
// Cost 2 - Pirated Starfighter - [Cunning] - Power 2 - HP 4
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: Return a friendly non-leader unit to its owner's hand.

// SOR_209 Pirated Starfighter — When Played: Return a friendly non-leader unit to hand
// (mandatory). (Raid 1 is an auto keyword.)
$whenPlayedAbilities["SOR_209:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'my', 'nonLeader' => true,
        'prompt' => "Return_a_friendly_non-leader_unit_to_hand",
    ]);
};
