<?php
// LOF_041
// Drain Essence
// Text: Deal 2 damage to a unit. The Force is with you (create your Force token).

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_041:0"] = function($player, $mzID = '') {
// Drain Essence — "Deal 2 damage to a unit. The Force is with you."
            // The Force creation is unconditional (separate sentence); the deal-2 fizzles cleanly with
            // no units in play.
            TheForceIsWithYou(intval($player));
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2,
                'prompt' => "Deal_2_damage_to_a_unit",
            ]);
            return;
};
