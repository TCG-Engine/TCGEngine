<?php
// SEC_210
// Cost 1 - Stolen Starpath Unit - [Cunning,Heroism] - Upgrade Power 1 - Upgrade HP 1
// Text: Attached unit gains: "On Attack: Name a card. The defending player reveals their hand. For each card in their hand with that name, create a Spy token."

// SEC_210 Stolen Starpath Unit (Upgrade) — granted "On Attack: name a card; for each copy in the
// defending player's hand, create a Spy token." Rides the generic upgrade On Attack seam (fires with
// the HOST mzID when a unit bearing SEC_210 attacks).
$onAttackAbilities["SEC_210:0"] = function($player, $mzID) {
    // With the defending player's hand empty there is nothing to reveal and no copies to count, so the
    // naming is meaningless (0 Spy tokens either way) — skip the whole ability (no NAMECARD prompt).
    if (count(GetHand(OtherPlayer(intval($player)))) === 0) return;
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_210#0", 1);
};

$customDQHandlers["SEC_210#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $named = trim($lastDecision);
    $opp   = OtherPlayer(intval($player));
    $count = 0;
    foreach (GetHand($opp) as $c) {
        if (!empty($c->removed)) continue;
        if (SWUObjectTitle($c) === $named) $count++;
    }
    SWUCreateUnitTokens(intval($player), 'SEC_T01', $count);

    // SEC_016 Padmé Amidala — "When you reveal … 1 or more cards from your hand." The hand revealed here
    // is the OPPONENT's, so the react fires for THEM ($opp), not the naming player. Same miss as SOR_185
    // Chimaera: the reveal was implicit in the count-the-copies loop and never announced to observers.
    if (function_exists('_SWUSec016React') && count(GetHand($opp)) > 0) {
        $savedPID = $playerID;
        _SWUSec016React($opp);
        $playerID = $savedPID;
    }
};
