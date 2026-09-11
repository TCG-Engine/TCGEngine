<?php
// ASH_045
// Cost 1 - Reanimated Night Trooper - [Vigilance,Villainy] - Power 2 - HP 2
// Text: When Defeated: Look at the top card of a deck. You may discard it.

// ASH_045 Reanimated Night Trooper — When Defeated: look at the top card of a deck. You may discard it.
$whenDefeatedAbilities["ASH_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1, tooltip: "Look_at_the_top_card_of_a_deck");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_045#0", 1);
};

$customDQHandlers["ASH_045#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $target = SWUDecodePlayerPick($lastDecision, intval($player)); // "You"→caster, "Opponent"/"P{n}"→that player
    $deck = GetDeck($target);
    if (empty($deck) || empty($deck[0]) || !empty($deck[0]->removed)) return;
    $topCid = $deck[0]->CardID ?? '';
    // ★ USER DECISION 2026-09-11: the LOOK is public (whose deck), the CARD only to the trooper's controller.
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' looked at the top card of '
        . ($target === intval($player) ? 'their' : "P{$target}'s") . ' deck' . SWULogSourceSuffix());
    SWULogPrivate(intval($player), 'REVEAL', 'You saw ' . GameLogCardRef($topCid));
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Discard_" . str_replace(' ', '_', CardTitle($topCid)) . "?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_045#1|{$target}", 1);
};

$customDQHandlers["ASH_045#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') return;
    SWUMillTopCard(intval($parts[0] ?? $player));
};
