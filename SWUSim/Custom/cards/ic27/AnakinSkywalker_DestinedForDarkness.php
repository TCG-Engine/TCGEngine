<?php
// IC27_078
// Cost 5 - Anakin Skywalker - Destined For Darkness - [Command,Heroism] - Unit (Ground) 7/4 (unique)
//   Traits: Force, Jedi, Republic
// Text: When Defeated: Search your deck for a card named Darth Vader, reveal it, and draw it.
//       While this unit is in your discard pile, ignore the aspect penalties on cards you play
//       named Darth Vader.
//
// Two independent seams on one card. The aspect-penalty waiver is hooked at the SWUAspectPenalty
// chokepoint in GameLogic (the SOR_008 Hera shape), which covers every play path and the affordability
// glow at once; this file owns the discard-pile predicate it reads plus the When Defeated search.

// "While this unit is in your DISCARD PILE" — deliberately NOT "in play" and NOT "you control it
// anywhere". Scans the player's own discard only, and does not touch $playerID: SWUAspectPenalty is
// called from many cost contexts and must stay side-effect free.
function _SWUAnakinIC27078InDiscard(int $player): bool {
    foreach (GetDiscard($player) as $d) {
        if (empty($d->removed) && ($d->CardID ?? '') === 'IC27_078') return true;
    }
    return false;
}

// "Search your deck for a card named Darth Vader, reveal it, and draw it." Searching the ENTIRE deck
// is DoTopDeckSearch over its full size (peeking the whole deck IS a full search, and the finalize
// reshuffles what is left). Matched by TITLE so any printing/subtitle of Darth Vader qualifies; a
// no-match search returns the peeked cards rather than milling them.
$whenDefeatedAbilities["IC27_078:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $deckSize = count(GetDeck(intval($player)));
    if ($deckSize <= 0) return;
    DoTopDeckSearch(intval($player), $deckSize,
        fn($c) => CardTitle($c) === 'Darth Vader', 1);
};
