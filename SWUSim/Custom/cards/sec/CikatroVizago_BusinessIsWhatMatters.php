<?php
// SEC_218
// Cost 3 - Cikatro Vizago - Business is What Matters - [Cunning] - Power 3 - HP 4
// Text: On Attack: Reveal the top card of your deck. An opponent may pay 1 resource. If they don't, draw that card.

// SEC_218 Cikatro Vizago — On Attack: reveal the top card of your deck. An opponent may pay 1 resource.
// If they don't, draw that card. (Peek the top — don't remove — so a normal draw resolves it.)
$onAttackAbilities["SEC_218:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $deck = &GetDeck(intval($player));
    if (count($deck) === 0) return;
    $cid = $deck[0]->CardID;
    AddGameLogEntry('REVEAL', "P" . intval($player) . " revealed " . GameLogCardRef($cid));
    // "AN opponent may pay 1 resource. If they don't, draw that card." The attacker picks WHO is offered.
    // ⚠ TWO QUESTIONS THAT MUST BE KEPT APART, and the old code conflated them:
    //   • WHO may be picked → EVERY live opponent. There is no per-opponent fizzle: whichever seat you
    //     name, the ability fully resolves (either they pay, or you draw). Naming a BROKE opponent even
    //     GUARANTEES the draw, which is a materially different play from naming a rich one — so filtering
    //     to "opponents who can pay" would delete the caster's most reliable line.
    //   • IS THE CHOICE MEANINGFUL AT ALL → only if at least ONE opponent can pay. If nobody can, every
    //     answer collapses to "the attacker draws", which IS degenerate — resolve it without a prompt.
    $anyCanPay = false;
    foreach (OpponentsOf(intval($player)) as $o) {
        if (SWUTotalPaymentCapacity($o) >= 1) { $anyCanPay = true; break; }   // Credits/Droids count (CR 3.13)
    }
    if (!$anyCanPay) {
        DoDrawCard(intval($player), 1);
        return;
    }
    SWUQueueChooseOpponent(intval($player), "SEC_218#1|" . intval($player),
        "Choose_an_opponent_to_offer_the_payment");
};

// Picked seat in $lastDecision; hand the pay-or-not choice to THAT opponent, on their own queue.
$customDQHandlers["SEC_218#1"] = function($player, $parts, $lastDecision) {
    $attacker = intval($parts[0] ?? $player);
    $opp      = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $attacker) return;
    // A chosen opponent who cannot pay simply does not get a prompt — their only legal answer is "no".
    if (SWUTotalPaymentCapacity($opp) < 1) { DoDrawCard($attacker, 1); return; }
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Pay_1_resource_to_stop_them_drawing_the_revealed_card?");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SEC_218#0|" . $attacker, 1);
};

$customDQHandlers["SEC_218#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $attacker = intval($parts[0] ?? 0);
    $opp = intval($player);   // the decision owner is the opponent
    if ($lastDecision === 'YES' && SWUTotalPaymentCapacity($opp) >= 1 && SWUPayInlineAbilityCost($opp, 1)) {
        return;               // opponent paid → no draw (card stays on top)
    }
    $playerID = $attacker;
    DoDrawCard($attacker, 1); // not paid → attacker draws the revealed top card
};
