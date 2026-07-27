<?php
// LAW_011
// Cost 7 - Darth Vader - Unstoppable - [Aggression,Villainy] - Power 6 - HP 8
// Text: Action [Exhaust, discard a card from your hand]: Deal 1 damage to a unit or base.
// DeployText: On Attack: Discard any number of cards from your hand. Deal damage to a unit or base equal to the number of cards discarded this way.
// Epic Action: If you control 7 or more resources, deploy this leader.

// ── LAW_011 Darth Vader ───────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your hand]: deal 1 damage to a unit or base.
// Deployed On Attack: discard any number of cards from hand; deal that many to a unit or base.
$leaderAbilities["LAW_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "LAW_011#0");
};

$customDQHandlers["LAW_011#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $targets = _SWUAllUnitsAndBases(intval($player));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit_or_base", "DEAL_TARGET|1");
    SWUQueueAfterAction(intval($player));
};

$onAttackAbilities["LAW_011:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) return;
    $max = count($hand);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $hand), 1, tooltip: "Discard_any_number_of_cards_from_your_hand");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_011#1", 1);
};

$customDQHandlers["LAW_011#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $mzs = array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS');
        // discard highest hand index first so earlier indices don't shift out from under later picks
        usort($mzs, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
        foreach ($mzs as $mz) { DoDiscardCard(intval($player), $mz); $n++; }
    }
    DecisionQueueController::CleanupRemovedCards();
    if ($n <= 0) return;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_TARGET', 'amount' => $n, 'includeBases' => true,
        'prompt' => "Deal_{$n}_damage_to_a_unit_or_base",
    ]);
};
