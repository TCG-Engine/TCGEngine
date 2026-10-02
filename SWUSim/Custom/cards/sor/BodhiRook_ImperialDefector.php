<?php
// SOR_201
// Cost 3 - Bodhi Rook - Imperial Defector - [Cunning,Cunning] - Power 3 - HP 3
// Text: When Played: Look at an opponent's hand and discard a non-unit card from it.

// SOR_201 Bodhi Rook — "When Played: Look at an opponent's hand and discard a non-unit card from it."
// The discard options, shared by the play and by the post-pick continuation (SWUOfferDiscardFromAnOpponent).
function _SWUOppDiscardOpts_SOR_201(): array {
    return ['from'=>'opp', 'filter'=>fn($cid)=>stripos(CardType($cid) ?? '', 'unit') === false, 'prompt'=>"Discard_a_non-unit_card_from_the_opponent's_hand", 'showHandIfAuto'=>true];
}

$whenPlayedAbilities["SOR_201:0"] = function($player, $mzID) {
    // showHandIfAuto: with 0/1 legal target the discard auto-resolves, so show the hand Viper-Probe-style.
    // "AN opponent" — the caster picks whose (Twin Suns).
    SWUOfferDiscardFromAnOpponent(intval($player), '_SWUOppDiscardOpts_SOR_201');
};
