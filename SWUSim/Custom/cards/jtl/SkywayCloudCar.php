<?php
// JTL_220
// Cost 3 - Skyway Cloud Car - [Cunning] - Power 3 - HP 3
// Text: When Defeated: You may return a non-leader unit with 2 or less power to its owner's hand.

// ── JTL_220 Skyway Cloud Car — When Defeated: may return a non-leader unit with 2 or less power to its
// owner's hand. ──────────────────────────────────────────────────────────────────────────────────────
$whenDefeatedAbilities["JTL_220:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => ObjectCurrentPower($o) <= 2,
        'question' => "You_may_return_a_non-leader_unit_with_2_or_less_power", 'prompt' => "Return_to_hand",
    ]);
};
