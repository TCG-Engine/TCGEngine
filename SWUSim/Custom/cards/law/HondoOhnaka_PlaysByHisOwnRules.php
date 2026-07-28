<?php
// LAW_094
// Cost 5 - Hondo Ohnaka - Plays By His Own Rules - [Cunning,Vigilance] - Power 3 - HP 7
// Text: You may look at the top card of your deck at any time. / Action: Play the top card of your deck (paying its cost). Use this ability only once each round.

// LAW_094 Hondo Ohnaka — "Action: Play the top card of your deck (paying its cost). Once each round."
$unitActionCostKind["LAW_094"] = 'none';

// LAW_094 Hondo Ohnaka — Action: play the top card of your deck (paying its cost). Once each round.
$unitAbilities["LAW_094"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Defensive: don't play (or consume the once-per-round) a top card blocked by a play-restriction
    // (SOR_062 Regional Governor). The availability gate already refuses this, but guard here too.
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    $deck   = GetDeck(intval($player));
    $topCid = ($topIdx !== -1) ? ($deck[$topIdx]->CardID ?? '') : '';
    if ($topCid === '' || SWUCardPlayBlocked(intval($player), $topCid)) { SWUAfterAction($player); return; }
    AddGlobalEffects(intval($player), 'SWU_LAW094_USED');
    SWUPlayTopDeckCard(intval($player), false, 0);
    SWUAfterAction($player);
};
