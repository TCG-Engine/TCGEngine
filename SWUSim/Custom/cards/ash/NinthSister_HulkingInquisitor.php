<?php
// ASH_148
// Cost 7 - Ninth Sister - Hulking Inquisitor - [Aggression,Villainy] - Power 8 - HP 7
// Text: Overwhelm / When Played: An opponent discards a card from their hand. You may deal damage equal to its cost divided as you choose among any number of units.

// ASH_148 Ninth Sister — Overwhelm (keyword) + When Played: an opponent discards a card from their hand;
// you may deal damage equal to its cost divided as you choose among any number of units. The opponent's
// discard resolves first (sync if they hold 1, else their MZCHOOSE), then ASH_148#0 reads the just-
// discarded card (top of their discard) and offers the up-to split (MZSPLITASSIGN UPTO → SPLIT_DAMAGE).
$whenPlayedAbilities["ASH_148:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $handCount = 0;
    foreach (GetHand($opp) as $c) { if (empty($c->removed)) $handCount++; }
    if ($handCount === 0) return;   // no card to discard → no damage
    SWUDiscardCards(intval($player), 1);   // opponent discards 1
    // The follow-up must run AFTER the discard so it can read the discarded card's cost. When the
    // opponent holds exactly 1 card SWUDiscardCards resolves synchronously, so the follow-up goes on
    // the controller's own queue (drained as part of this action). When they hold 2+ cards their
    // choice is queued on THEIR queue; queue the follow-up there too (FIFO → after the discard) so it
    // can't fire first and read an empty discard. Either way carry the controller in $parts[0].
    if ($handCount > 1)
        DecisionQueueController::AddDecision($opp, "CUSTOM", "ASH_148#0|" . intval($player), 1);
    else
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_148#0|" . intval($player), 1);
};

$customDQHandlers["ASH_148#0"] = function($queueOwner, $parts, $lastDecision) {
    $player = intval($parts[0] ?? $queueOwner);   // the ASH_148 controller (deals the damage)
    global $playerID; $playerID = $player;
    $opp = OtherPlayer($player);
    $discard = GetDiscard($opp);
    $cost = -1;
    for ($i = count($discard) - 1; $i >= 0; $i--) {   // the just-discarded card = last non-removed entry
        if (!empty($discard[$i]->removed)) continue;
        $cost = intval(CardCost($discard[$i]->CardID ?? '')); break;
    }
    if ($cost <= 0) return;   // 0-cost (or none) → no damage to deal
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    DecisionQueueController::AddDecision(intval($player), "MZSPLITASSIGN", "{$cost}|" . implode("&", $targets) . "|UPTO", 1,
        tooltip: "Divide_up_to_{$cost}_damage_among_any_number_of_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SPLIT_DAMAGE", 1);
};
