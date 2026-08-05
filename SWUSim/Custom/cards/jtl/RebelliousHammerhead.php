<?php
// JTL_153
// Cost 6 - Rebellious Hammerhead - [Aggression,Heroism] - Power 5 - HP 7
// Text: When Played: You may deal damage to a unit equal to the number of cards in your hand.

// ── JTL_153 Rebellious Hammerhead — When Played: You may deal damage to a unit equal to the number of
// cards in your hand (counted at resolution, after this card has left your hand). ────────────────────
$whenPlayedAbilities["JTL_153:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $amount = count(ZoneSearch("myHand"));
    if ($amount <= 0) return;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_equal_to_cards_in_hand", "Deal_{$amount}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};
