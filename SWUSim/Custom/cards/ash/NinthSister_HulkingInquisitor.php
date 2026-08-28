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
    // "AN opponent" — the caster picks among opponents holding a card (auto-resolves at one, so 2-player
    // is unchanged). Was OtherPlayer(): one seat, unreachable seats 3/4.
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;   // no card to discard anywhere → no damage
    // Ninth Sister deals the damage her ability deals (CR 9.12); her source token rides both hops.
    SWUQueueChooseOpponent(intval($player), "ASH_148#OPP|" . intval($player) . "|"
        . _SWUEncodeDamageSource($mzID),
        "Which_opponent_discards_a_card?", $eligible);
};

// The picked seat discards; the damage rider then reads THAT seat's discard pile for the card's cost.
$customDQHandlers["ASH_148#OPP"] = function($queueOwner, $parts, $lastDecision) {
    global $playerID;
    $player = intval($parts[0] ?? $queueOwner);
    $srcTok = (string)($parts[1] ?? '');
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $playerID = $player;
    $handCount = 0;
    foreach (GetHand($opp) as $c) { if (empty($c->removed)) $handCount++; }
    if ($handCount === 0) return;
    SWUDiscardCards($player, 1, $opp);   // the picked opponent discards 1
    // The follow-up must run AFTER the discard so it can read the discarded card's cost. When the
    // opponent holds exactly 1 card SWUDiscardCards resolves synchronously, so the follow-up goes on
    // the controller's own queue (drained as part of this action). When they hold 2+ cards their
    // choice is queued on THEIR queue; queue the follow-up there too (FIFO → after the discard) so it
    // can't fire first and read an empty discard. Either way carry the controller in $parts[0].
    if ($handCount > 1)
        DecisionQueueController::AddDecision($opp, "CUSTOM", "ASH_148#0|{$player}|{$opp}|{$srcTok}", 1);
    else
        DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_148#0|{$player}|{$opp}|{$srcTok}", 1);
};

$customDQHandlers["ASH_148#0"] = function($queueOwner, $parts, $lastDecision) {
    $player = intval($parts[0] ?? $queueOwner);   // the ASH_148 controller (deals the damage)
    global $playerID; $playerID = $player;
    $opp = intval($parts[1] ?? 0);   // the seat that discarded — rides the param, never guessed
    $srcMz = _SWUDecodeDamageSource((string)($parts[2] ?? ''));   // Ninth Sister, the dealer (CR 9.12)
    if ($opp <= 0) return;
    $discard = GetDiscard($opp);
    $cost = -1;
    for ($i = count($discard) - 1; $i >= 0; $i--) {   // the just-discarded card = last non-removed entry
        if (!empty($discard[$i]->removed)) continue;
        $cost = intval(CardCost($discard[$i]->CardID ?? '')); break;
    }
    if ($cost <= 0) return;   // 0-cost (or none) → no damage to deal
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUOfferSplitDamage(intval($player), intval($cost), $targets,
        "Divide_up_to_{$cost}_damage_among_any_number_of_units", true, false, $srcMz);
};
