<?php
// IBH_074 / IBH_102
// Cost 2 - I Want Proof, Not Leads - [Aggression,Villainy]
// Text: Draw 2 cards, then discard a card from your hand.

// Shared by both printings. The event being resolved is still physically in hand (it's discarded
// later at block 10); exclude it from "a card from your hand" by dropping the first hand card
// matching the played CardID — so each printing must pass its own CardID.
function IWantProofNotLeadsPlay(int $player, string $cardID): void {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 2);
    $hand = ZoneSearch("myHand");
    $excluded = false; $targets = [];
    foreach ($hand as $mz) {
        $o = GetZoneObject($mz);
        if (!$excluded && $o !== null && ($o->CardID ?? '') === $cardID) { $excluded = true; continue; }
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_from_your_hand",
        "DISCARD_FROM_OWN_HAND|" . intval($player));
}
$whenPlayedAbilities["IBH_074:0"] = function($player, $mzID = '') { IWantProofNotLeadsPlay(intval($player), 'IBH_074'); };
$whenPlayedAbilities["IBH_102:0"] = function($player, $mzID = '') { IWantProofNotLeadsPlay(intval($player), 'IBH_102'); };
