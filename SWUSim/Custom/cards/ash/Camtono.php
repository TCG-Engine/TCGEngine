<?php
// ASH_229
// Cost 2 - Camtono - [Cunning] - Upgrade Power 0 - Upgrade HP 0
// Text: Attached unit gains: "When Attack Ends: Look at the top card of your deck. If it costs 2 or less, you may play it for free."

// ASH_229 Camtono (upgrade) — attached unit gains "When Attack Ends: look at the top card of your deck; if
// it costs 2 or less, you may play it for free." (SWUPlayTopDeckCard → ActivateCard handles any card type.)
$onAttackEndFromUpgradeAbilities["ASH_229"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID = GetDeck(intval($player))[$idx]->CardID ?? '';
    if ($topID === '') return;
    if (intval(CardCost($topID)) > 2) {
        // Too expensive to play — but "Look at the top card of your deck" still happens, so SHOW it (the
        // SOR_238 C-3PO whiff pattern). Returning silently made the trigger look like it never fired (#1105).
        DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@{$topID}&OK", 1,
            tooltip: "Camtono:_the_top_card_costs_more_than_2_-_it_stays_on_top");
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Play_" . GameLogCardRef($topID) . "_from_the_top_of_your_deck_for_free?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_229#0", 1);
};

$customDQHandlers["ASH_229#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    SWUPlayTopDeckCard(intval($player), true);   // free
};
