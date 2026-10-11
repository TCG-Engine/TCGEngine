<?php
// JTL_207
// Jam Communications
// Text: Look at an opponent's hand and discard an event from it.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_207:0"] = function($player, $mzID = '') {
// Spy Net — "Look at an opponent's hand and discard an event from it."
            SWUOfferDiscardFromAnOpponent(intval($player), '_SWUOppDiscardOpts_JTL_207');   // "AN opponent": the caster picks (Twin Suns)
            return;
};

// The discard options, shared by the play and by the post-pick continuation (SWUOfferDiscardFromAnOpponent).
function _SWUOppDiscardOpts_JTL_207(): array {
    return ['from'=>'opp', 'filter'=>fn($cid)=>stripos(CardType($cid) ?? '', 'event') !== false, 'prompt'=>"Discard_an_event_from_the_opponent's_hand", 'showHandIfAuto'=>true];
}
