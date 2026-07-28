<?php
// LOF_226
// Tip the Scale
// Text: Look at an opponent's hand and discard a non-unit card from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_226:0"] = function($player, $mzID = '') {
// Tip the Scale — "Look at an opponent's hand and discard a non-unit card from it."
            SWUOfferDiscard($player, ['from'=>'opp', 'filter'=>fn($cid)=>stripos(CardType($cid) ?? '', 'unit') === false, 'prompt'=>"Discard_a_non-unit_card_from_the_opponent's_hand", 'showHandIfAuto'=>true]);
            return;
};
