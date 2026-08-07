<?php
// IC27_008
// Cost 6 - Princess Leia - On a Diplomatic Mission - [Cunning,Heroism] - Leader / Unit (Ground) 4/7
//   Traits: Rebel, Official
// Text: Action [1 resource, Exhaust]: Draw a card, then put a card from your hand on the top or
//       bottom of your deck.
// DeployText: On Attack: Draw a card, then put a card from your hand on the top or bottom of your deck.
// Epic Action: If you control 6 or more resources, deploy this leader.
//
// Both sides run the SAME clause (verbatim TWI_004 Yoda), differing only in who owns the After Action:
// the front Action must close it, while the deployed On Attack lets combat own it. That is threaded as
// a trailing `close` flag on the continuation param (the TS26_03 Maul shape) rather than duplicating
// the flow. Epic deploy needs no wiring — the generic threshold IS the leader's printed cost (6).

// Affordability gate for the front side's [1 resource] component. The registry is read by
// SWULeaderActionAffordable BEFORE the leader exhausts, so an unaffordable action is a clean no-op
// rather than a soft pass that spends the leader. ($leaderActionResourceCosts is initialized in
// LeaderAbilities.php, which loads BEFORE cards/_loader.php, so appending here is safe.)
$leaderActionResourceCosts["IC27_008"] = 1;

// Shared: draw 1, then offer a hand card to put back. $close = 1 when the caller owns the After Action.
function Ic27008DrawThenReplace(int $player, int $close): void {
    global $playerID; $playerID = intval($player);
    DoDrawCard($player, 1);
    DecisionQueueController::CleanupRemovedCards();
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) {                        // empty deck + empty hand — nothing to put back
        if ($close === 1) SWUAfterAction($player);
        return;
    }
    SWUQueueChooseTarget($player, $hand,
        "Put_a_card_on_the_top_or_bottom_of_your_deck", "IC27_008#0|{$close}");
}

$customDQHandlers["IC27_008#0"] = function($player, $parts, $lastDecision) {
    $close = intval($parts[0] ?? 0);
    global $playerID; $playerID = intval($player);
    $o = SWUDecisionDeclined($lastDecision) ? null : GetZoneObject($lastDecision);
    if (SWUObjGone($o)) {
        if ($close === 1) SWUAfterAction(intval($player));
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Top&Bottom", 1,
        tooltip: "Put_the_card_on_top_or_bottom_of_your_deck");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM",
        "IC27_008#1|{$close}|" . ($o->CardID ?? ''), 1);
};

$customDQHandlers["IC27_008#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $close = intval($parts[0] ?? 0);
    $cid   = $parts[1] ?? '';
    // Re-find the card in hand by CardID — the index may have shifted since it was chosen.
    DecisionQueueController::CleanupRemovedCards();
    $handMz = null;
    foreach (array_values(ZoneSearch("myHand")) as $mz) {
        $c = GetZoneObject($mz);
        if ($c !== null && ($c->CardID ?? '') === $cid) { $handMz = $mz; break; }
    }
    if ($handMz !== null) {
        $c = GetZoneObject($handMz);
        $c->Remove();
        DecisionQueueController::CleanupRemovedCards();
        if ($lastDecision === 'Top') {
            $deck = &GetDeck(intval($player));
            $obj  = new Deck($cid, 'Deck', intval($player));
            array_unshift($deck, $obj);
            foreach ($deck as $i => $card) { $card->mzIndex = $i; }
        } else {
            _topDeckPutRemainingToBottom(intval($player), [$cid]);
        }
    }
    if ($close === 1) SWUAfterAction(intval($player));
};

// Front (undeployed) Action — SWULeaderAction exhausts the leader and pays the [1 resource] (through
// the Credit/Droid alt-pay funnel) before this runs. The action owns its After Action.
$leaderAbilities["IC27_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    Ic27008DrawThenReplace($player, 1);
};

// Deployed (leader unit) On Attack — no cost, and combat owns the After Action.
$onAttackAbilities["IC27_008:0"] = function($player, $mzID) {
    Ic27008DrawThenReplace(intval($player), 0);
};
