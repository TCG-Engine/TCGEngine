<?php
// JTL_119
// Cost 6 - Resupply Carrier - [Command] - Power 4 - HP 5
// Text: When Played: You may put the top card of your deck into play as a resource.

// ── JTL_119 Resupply Carrier — When Played: You may put the top card of your deck into play as a
// resource. ──────────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_119:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $hasCard = false;
    foreach (GetDeck(intval($player)) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Put_top_of_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_119#0', 1);
};

$customDQHandlers["JTL_119#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $deck = GetDeck(intval($player));
    $topIdx = null;
    foreach ($deck as $i => $c) { if (empty($c->removed)) { $topIdx = $i; break; } }
    if ($topIdx === null) return;
    SWURampResourceExhausted(intval($player), "myDeck-" . $topIdx); // JTL_119: enters exhausted (no "ready" wording)
};
