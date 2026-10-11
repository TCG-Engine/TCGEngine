<?php
// SOR_200
// Spark of Rebellion
// Text: Look at an opponent's hand and discard a card from it.

// The discard options, shared by the play and by the post-pick continuation (SWUOfferDiscardFromAnOpponent).
function _SWUOppDiscardOpts_SOR_200(): array {
    return ['from'=>'opp', 'prompt'=>"Discard_a_card_from_the_opponent's_hand"];
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_200:0"] = function($player, $mzID = '') {
// Spark of Rebellion — "Look at AN OPPONENT's hand and discard a card from it." — the caster picks whose
// (Twin Suns; bug report 2026-10-01). One opponent: the old call, unchanged.
            SWUOfferDiscardFromAnOpponent(intval($player), '_SWUOppDiscardOpts_SOR_200');
            return;
};
