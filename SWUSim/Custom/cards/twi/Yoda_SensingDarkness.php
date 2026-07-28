<?php
// TWI_004
// Cost 7 - Yoda - Sensing Darkness - [Vigilance,Heroism] - Power 4 - HP 9
// Text: Action [Exhaust]: If a unit left play this phase, draw a card, then put a card from your hand on the top or bottom of your deck.
// DeployText: Restore 2 / When Deployed: You may discard a card from your deck. If you do, defeat an enemy non-leader unit that costs the same as or less than the discarded card.
// Epic Action: If you control 7 or more resources, deploy this leader.

// TWI_004 Yoda (front continuation) — put the chosen hand card on the top or bottom of the deck.
$customDQHandlers["TWI_004#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Top&Bottom", 1, tooltip: "Put_the_card_on_top_or_bottom_of_your_deck");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_004#1|" . intval($o->UniqueID ?? 0) . "|" . $o->CardID, 1);
};

$customDQHandlers["TWI_004#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cid = $parts[1] ?? '';
    // Re-find the card in hand by CardID (index may have shifted).
    DecisionQueueController::CleanupRemovedCards();
    $handMz = null;
    foreach (array_values(ZoneSearch("myHand")) as $mz) { $c = GetZoneObject($mz); if ($c !== null && ($c->CardID ?? '') === $cid) { $handMz = $mz; break; } }
    if ($handMz !== null) {
        $c = GetZoneObject($handMz);
        $c->Remove();
        DecisionQueueController::CleanupRemovedCards();
        if ($lastDecision === 'Top') {
            $deck = &GetDeck(intval($player));
            $obj = new Deck($cid, 'Deck', intval($player));
            array_unshift($deck, $obj);
            foreach ($deck as $i => $card) { $card->mzIndex = $i; }
        } else {
            _topDeckPutRemainingToBottom(intval($player), [$cid]);
        }
    }
    SWUAfterAction(intval($player));
};

// TWI_004 Yoda (deployed) — Restore 2 (keyword) + "When Deployed: You may discard a card from your deck.
// If you do, defeat an enemy non-leader unit that costs the same as or less than the discarded card."
$whenPlayedAbilities["TWI_004:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Discard_the_top_card_of_your_deck_to_defeat_an_enemy_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_004#2", 1);
};

// TWI_004 Yoda (front) — "Action [Exhaust]: If a unit left play this phase, draw a card, then put a card
// from your hand on the top or bottom of your deck." (Affordability gates the left-play condition.)
$leaderAbilities["TWI_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    DoDrawCard($player, 1);
    DecisionQueueController::CleanupRemovedCards();
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $hand, "Put_a_card_on_the_top_or_bottom_of_your_deck", "TWI_004#0");
};

$customDQHandlers["TWI_004#2"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  global $playerID;
  $playerID = intval($player);
  $milled = SWUMillTopCard(intval($player)); // discard top of own deck; returns its CardID
  if ($milled === null)
    return;
  $maxCost = intval(CardCost($milled));
  SWUOfferUnitTarget($player, '', [
      'continuation' => 'DEFEAT_UNIT', 'side' => 'their', 'nonLeader' => true,
      'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= $maxCost,
      'prompt' => "Defeat_an_enemy_non-leader_unit_costing_{$maxCost}_or_less",
  ]);
};
