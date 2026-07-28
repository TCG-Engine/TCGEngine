<?php
// ASH_260
// Cost 2 - Mos Espa Watermonger - Power 1 - HP 3
// Text: When Played: You may draw a card. If you do, discard a card.

// ASH_260 Mos Espa Watermonger — When Played: you may draw a card; if you do, discard a card. ("Loot.")
$whenPlayedAbilities["ASH_260:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Draw_a_card,_then_discard_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_260#0", 1);
};

$customDQHandlers["ASH_260#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined → no draw, no discard
    DoDrawCard(intval($player), 1);
    $hand = [];
    foreach (ZoneSearch("myHand", null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $hand[] = $mz;
    }
    if (empty($hand)) return;   // empty hand (deck was empty) → nothing to discard
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card", "ASH_260#1");
};

$customDQHandlers["ASH_260#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DoDiscardCard(intval($player), $lastDecision);
};
