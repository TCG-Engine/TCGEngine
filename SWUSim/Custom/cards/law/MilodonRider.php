<?php
// LAW_240
// Cost 6 - Milodon Rider - [Cunning] - Power 5 - HP 6
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may return another friendly non-leader unit to its owner's hand.

// LAW_240 Milodon Rider — Ambush + When Played: you may return another friendly non-leader unit to its
// owner's hand.
$whenPlayedAbilities["LAW_240:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'my', 'nonLeader' => true, 'excludeSelf' => true, 'may' => true,
        'question' => "Return_another_friendly_non-leader_unit_to_hand?", 'prompt' => "Choose_a_unit",
    ]);
};
