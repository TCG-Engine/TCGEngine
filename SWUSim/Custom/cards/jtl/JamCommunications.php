<?php
// JTL_207
// Jam Communications
// Text: Look at an opponent's hand and discard an event from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_207:0"] = function($player, $mzID = '') {
// Spy Net — "Look at an opponent's hand and discard an event from it."
            SWUOfferDiscard($player, ['from'=>'opp', 'filter'=>fn($cid)=>stripos(CardType($cid) ?? '', 'event') !== false, 'prompt'=>"Discard_an_event_from_the_opponent's_hand", 'showHandIfAuto'=>true]);
            return;
};
