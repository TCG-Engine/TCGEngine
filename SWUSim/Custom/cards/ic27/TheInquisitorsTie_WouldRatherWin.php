<?php
// IC27_104
// Cost 4 - The Inquisitor's TIE - Would Rather Win - [Aggression,Villainy] - Unit (Space) 4/5 (unique)
//   Traits: Imperial, Vehicle, Fighter, Inquisitor
// Text: On Attack: Each player with 4 or more cards in their hand discards a card from their hand.

// SYMMETRIC — "each player" includes the attacker's own controller, so this is NOT SWUDiscardCards
// (which targets only the opponent). Each qualifying player chooses from their OWN hand
// (SWUOfferDiscard from:'own' -> DISCARD_FROM_OWN_HAND), active player first, mirroring LAW_204
// Every Day More Lies / SEC_147 Chopper.
//
// The per-player 4-card threshold is read for BOTH players before either discard resolves (the
// offers are queued, not applied inline), so the gate is evaluated simultaneously as the ability
// resolves rather than sequentially.
//
// ⚠ Routed through an intermediate CUSTOM rather than queuing the picks straight from the On Attack
// closure: OnAttackTrigger restores $playerID before MZCountChoices runs, so a mandatory relative-mzID
// MZCHOOSE queued in the closure counts 0 choices and is silently skipped. ExecuteStaticMethods does
// NOT restore $playerID around a CUSTOM, so the picks validate correctly from there — and $playerID is
// deliberately left on the last offered player rather than restored.
$onAttackAbilities["IC27_104:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_104#0', 1);
};

$customDQHandlers["IC27_104#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    foreach ([intval($player), OtherPlayer(intval($player))] as $p) {
        $playerID = $p;
        $handCount = 0;
        foreach (GetHand($p) as $h) if (empty($h->removed)) $handCount++;
        if ($handCount >= 4) SWUOfferDiscard($p, ['from' => 'own']);
    }
};
