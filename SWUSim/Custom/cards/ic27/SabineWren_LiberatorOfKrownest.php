<?php
// IC27_119
// Cost 4 - Sabine Wren - Liberator of Krownest - [Aggression,Heroism] - Unit (Ground) 5/4 (unique)
//   Traits: Mandalorian, Rebel, Spectre
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.)
//       When Played: Discard 2 cards from a deck. You may deal damage to a unit equal to the difference in those
//       cards' costs.
//
// Saboteur is in $Saboteur_Cards. "a deck" is the LAW_018 Lando Calrissian picker: any live seat's NON-EMPTY deck,
// your own included (SWUSeatsWithNonEmptyDeck / SWUDeckPickerLabels / SWUDecodeDeckPick); one eligible deck resolves
// without a prompt, none fizzles. "Discard" from a deck = the TOP cards (LAW_018 ruling: "always discard from the top of
// a deck, unless an ability specifies otherwise") — SWUMillTopCard twice.
// The difference is |printed cost 1 - printed cost 2| (Death by Droids ruling: a card's cost is its printed cost).
// ⚠ PREVIEW JUDGEMENT CALLS: with fewer than 2 cards discarded (a 1-card deck) there is no difference, so nothing is
// offered; a difference of 0 is no damage, so nothing is offered either. "a unit" is unqualified: any unit on the table,
// Sabine included, optional (MZMAYCHOOSE). Sabine is the damage SOURCE (re-found by UniqueID — the deck pick can cross a
// request boundary).
$whenPlayedAbilities["IC27_119:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $uid = SWUObjUID(GetZoneObject($mzID), 0);
    $decks = SWUSeatsWithNonEmptyDeck(intval($player));
    if (empty($decks)) return;
    if (count($decks) === 1) { _SWUIc27119Discard(intval($player), intval($decks[0]), $uid); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE",
        "@-&" . SWUDeckPickerLabels(intval($player), "Your_deck&Opponent's_deck", $decks), 1, "Discard_2_cards_from_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_119#0|{$uid}", 1);
};

$customDQHandlers["IC27_119#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    _SWUIc27119Discard(intval($player), SWUDecodeDeckPick($lastDecision, intval($player)), intval($parts[0] ?? 0));
};

function _SWUIc27119Discard(int $player, int $deckOwner, int $sabineUID): void {
    global $playerID; $playerID = $player;
    $first  = SWUMillTopCard($deckOwner);
    $second = SWUMillTopCard($deckOwner);
    if ($first === null || $second === null) return;   // fewer than 2 discarded: no difference to deal
    $diff = abs(intval(CardCost($first)) - intval(CardCost($second)));
    if ($diff <= 0) return;
    $playerID = $player;
    $src = $sabineUID > 0 ? SWUFindMzByUID($sabineUID) : null;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $diff, 'may' => true,
        'source' => $src ?? '',
        'question' => "Deal_{$diff}_damage_to_a_unit?", 'prompt' => "Choose_a_unit_to_deal_{$diff}_damage_to",
    ]);
}
