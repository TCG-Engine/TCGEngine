<?php
// LOF_226
// Tip the Scale
// Text: Look at an opponent's hand and discard a non-unit card from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_226:0"] = function($player, $mzID = '') {
// Tip the Scale — "Look at an opponent's hand and discard a non-unit card from it."
            global $playerID; $playerID = intval($player);
            $tipTargets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'unit') === false);
            SWUQueueChooseTarget(intval($player), $tipTargets, "Discard_a_non-unit_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            if (count($tipTargets) <= 1) SWUQueueShowOpponentHand(intval($player));
            return;
};
