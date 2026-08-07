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
    $opp = OtherPlayer(intval($player));
    if (SWUTotalPaymentCapacity($opp) < 1) { // Credits/Droids can pay this 1 (CR 3.13)
        // Opponent cannot pay the 1 resource → no choice to offer; the attacker simply draws.
        DoDrawCard(intval($player), 1);
        return;
    }
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Pay_1_resource_to_stop_them_drawing_the_revealed_card?");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SEC_218#0|" . intval($player), 1);
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
