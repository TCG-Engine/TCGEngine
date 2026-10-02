<?php
// LOF_226
// Tip the Scale
// Text: Look at an opponent's hand and discard a non-unit card from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_226:0"] = function($player, $mzID = '') {
// Tip the Scale — "Look at an opponent's hand and discard a non-unit card from it."
            SWUOfferDiscardFromAnOpponent(intval($player), '_SWUOppDiscardOpts_LOF_226');   // "AN opponent": the caster picks (Twin Suns)
            return;
};

// The discard options, shared by the play and by the post-pick continuation (SWUOfferDiscardFromAnOpponent).
function _SWUOppDiscardOpts_LOF_226(): array {
    return ['from'=>'opp', 'filter'=>fn($cid)=>stripos(CardType($cid) ?? '', 'unit') === false, 'prompt'=>"Discard_a_non-unit_card_from_the_opponent's_hand", 'showHandIfAuto'=>true];
}
