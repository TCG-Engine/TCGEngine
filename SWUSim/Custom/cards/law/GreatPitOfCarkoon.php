<?php
// LAW_023
// Great Pit of Carkoon - [Command] - HP 27
// Text: Epic Action [discard a unit from your hand]: Search your deck for a card named The Sarlacc of Carkoon, reveal it, and draw it.

// Can the Epic's [discard a unit from your hand] cost be paid at all? Read by BOTH the availability
// computation and the dispatch gate in GameLogic.php, so an unpayable Epic is neither offered nor spent.
function _SWULaw023CanPayCost(int $player): bool {
    global $playerID; $saved = $playerID; $playerID = $player;
    $units = ZoneSearch("myHand", ["Unit"]);
    $playerID = $saved;
    return !empty($units);
}

// LAW_023 Great Pit of Carkoon — discard the chosen hand unit (cost), then search the whole deck for
// LAW_163 (The Sarlacc of Carkoon) and draw it.
$customDQHandlers["LAW_023#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);          // pay the [discard a unit] cost
    DecisionQueueController::CleanupRemovedCards();
    $deckSize = count(GetDeck(intval($player)));
    if ($deckSize > 0) DoTopDeckSearch(intval($player), $deckSize, fn($c) => $c === 'LAW_163', 1);
    SWUQueueAfterAction(intval($player));
};

// LAW_023 Great Pit of Carkoon — Epic Action [discard a unit from your hand]: Search your deck for a
// card named The Sarlacc of Carkoon (LAW_163), reveal it, and draw it.
$baseAbilities["LAW_023"] = function($player) {
    global $playerID; $playerID = intval($player);
    $handUnits = array_values(ZoneSearch("myHand", ["Unit"]));
    if (empty($handUnits)) { SWUAfterAction($player); return; }   // can't pay the cost
    SWUQueueChooseTarget(intval($player), $handUnits, "Discard_a_unit_from_your_hand_(cost)", "LAW_023#0");
};
