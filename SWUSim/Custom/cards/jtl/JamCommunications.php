<?php
// JTL_207
// Jam Communications
// Text: Look at an opponent's hand and discard an event from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_207:0"] = function($player, $mzID = '') {
// Spy Net — "Look at an opponent's hand and discard an event from it."
            global $playerID;
            $playerID = intval($player);
            $targets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'event') !== false);
            SWUQueueChooseTarget(intval($player), $targets, "Discard_an_event_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            if (count($targets) <= 1) SWUQueueShowOpponentHand(intval($player));
            return;
};
