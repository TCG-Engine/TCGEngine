<?php
// TS26_37
// Cost 4 - Abandoned the Order - [Cunning,Vigilance] - Upgrade Power 1 - Upgrade HP 1
// Text: Attached unit loses the Jedi trait and gains Restore 1. / When Played: You may return a non-leader unit to its owner's hand.

// TS26_37 Abandoned the Order (upgrade) — When Played: you may return a non-leader unit to its owner's
// hand. (Non-pilot upgrade → $mzID = the host; the target set is board-wide.)
$whenPlayedAbilities["TS26_37:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'question' => "Return_a_non-leader_unit_to_hand?", 'prompt' => "Choose_a_non-leader_unit",
    ]);
};
