<?php
// SHD_214
// Cost 3 - Frontier Trader - [Cunning] - Power 2 - HP 2
// Text: When Played: You may return a resource you control to its owner's hand. If you do, you may put the top card of your deck into play as a resource.

// ─── SHD_214 (When Played) ────────────────────────────────────────────────────
// When Played: You may return a resource you control to its owner's hand. If you do, you may put the
// top card of your deck into play as a resource. (Mirrors SEC_008's ramp, but both steps optional.)
$whenPlayedAbilities["SHD_214:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    $targets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) {
        if (isset($res[$i]->removed) && $res[$i]->removed) continue;
        $targets[] = "myResources-{$idx}"; $idx++;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_resource_you_control?", "Choose_a_resource_to_return", "SHD_214#0");
};

$customDQHandlers["SHD_214#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return; // declined
    if (!SWUReturnResourceToHand(intval($player), $lastDecision)) return;
    DecisionQueueController::CleanupRemovedCards();
    if (count(GetDeck(intval($player))) === 0) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip:"Put_the_top_card_of_your_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SHD_214#1", 1);
};

$customDQHandlers["SHD_214#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < count($deck); $i++) {
        if (isset($deck[$i]->removed) && $deck[$i]->removed) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources(intval($player), $top, 0, intval($player), intval($player)); // Status 0 = exhausted
        AddGameLogEntry('RESOURCE', 'P' . intval($player) . ' put a card into play as a resource');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
};
