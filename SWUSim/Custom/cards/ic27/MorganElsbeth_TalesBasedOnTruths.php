<?php
// IC27_179
// Cost 2 - Morgan Elsbeth - Tales Based On Truths - [Villainy] - Unit (Ground) 1/5 (unique)
//   Traits: Force, Imperial, Night
// Text: On Attack: Reveal the top card of your deck. If it costs 6 or more, draw it. Otherwise, discard it.
//
// PREVIEW card (no official rulings). Mandatory, no decision — the closest released shape is SHD_041 Kuiil
// (On Attack: discard the top card, then a branch on what it was).
//   • "your deck" is the CONTROLLER's ($player), so a stolen Morgan reads its new controller's deck.
//   • Only the TOP card is read. An empty deck reveals nothing, and "draw it" names the revealed card, so there
//     is no failed draw and no deck-out damage.
//   • Cost is PRINTED cost (CardCost), whatever the card's type — the card is never played, so no discount or
//     alternate price applies.
//   • "Draw it" goes through DoDrawCard (the revealed card IS the top card), so the draw observers fire (ASH_169
//     Axe Woves); "discard it" goes through SWUMillTopCard, which stamps From=DECK and fires the discard ones.
// Tests: SWUSim/Tests/Cases/ic27/MorganElsbeth_TalesBasedOnTruths.md

$onAttackAbilities["IC27_179:0"] = function($player, $mzID) {
    global $playerID;
    $me = intval($player);
    $playerID = $me;
    $idx = _SWUTopDeckFrontIdx($me);
    if ($idx === -1) return;                                         // empty deck: nothing revealed
    $top = (string)(GetDeck($me)[$idx]->CardID ?? '');
    if ($top === '') return;
    AddGameLogEntry('REVEAL', GameLogCardRef('IC27_179') . ' reveals the top of P' . $me . "'s deck: "
        . GameLogCardRef($top));                                     // public: "reveal", not "look"
    if (intval(CardCost($top)) >= 6) DoDrawCard($me, 1);
    else SWUMillTopCard($me);
    $playerID = $me;
};
