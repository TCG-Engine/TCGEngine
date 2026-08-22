<?php
// SEC_260
// Cost 2 - Inspector's Shuttle - Power 1 - HP 3
// Text: When Played: Name a card, then an opponent reveals their hand. For each copy of the named card in their hand, give an Experience token to this unit.

// SEC_260 Inspector's Shuttle — When Played: name a card; the opponent reveals their hand; for each copy
// of the named card in their hand, give an Experience token to this unit.
$whenPlayedAbilities["SEC_260:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    $uid = SWUObjUID($self, 0);
    // "…then AN OPPONENT reveals their hand." OFFICIAL RULING (10/31/2025): "If there are multiple
    // opponents, the controlling player chooses which one will be 'an opponent.'"
    // ⚠ FILTER to opponents holding a card: with an empty hand there is nothing to reveal, nothing to
    // count and no Experience possible — a choice among nothing. (This preserves the existing
    // WhenPlayed_OpponentHandEmpty_NoPrompt behaviour, which is now the zero-eligible case.)
    // ⚠ The seat is picked BEFORE the naming: you name a card knowing whose hand you are about to see,
    //   which is the printed order ("name a card, THEN an opponent reveals").
    $eligible = SWUOpponentsWithCards(intval($player));
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), "SEC_260#1|{$uid}",
        "Choose_an_opponent_to_reveal_their_hand", $eligible);
};

$customDQHandlers["SEC_260#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $opp = SWUPickedOpponent($lastDecision);
    if ($uid <= 0 || $opp <= 0 || $opp === intval($player)) return;
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_260#0|{$uid}|{$opp}", 1);
};

$customDQHandlers["SEC_260#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $named = trim($lastDecision);
    $opp = intval($parts[1] ?? 0);
    if ($opp <= 0 || $opp === intval($player)) return;
    // ⚠ ACTUALLY REVEAL THE HAND. The printed text — confirmed by the card's ERRATA (07/20/2026: "Name a
    // card, then an opponent REVEALS THEIR HAND") — is a real, observable event, but this handler used to
    // count the copies SILENTLY: the opponent's hand was inspected and nothing was ever shown or logged,
    // so the naming player had no way to verify the count and the revealing player never saw it happen.
    // SWULookAtOpponentHand logs the reveal (scoped to the two seats involved above two seats);
    // SWUQueueShowOpponentHand puts the cards on screen behind an OK acknowledgement — a game-log line
    // alone is too easy to miss for something the player is entitled to see.
    SWULookAtOpponentHand(intval($player), null, $opp);
    SWUQueueShowOpponentHand(intval($player), $opp);
    $count = 0;
    foreach (GetHand($opp) as $c) { if (!empty($c->removed)) continue; if (SWUObjectTitle($c) === $named) $count++; }
    $smz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($smz !== null) for ($i = 0; $i < $count; $i++) DoGiveExperienceToken(intval($player), $smz);

    // SEC_016 Padmé Amidala — "When you reveal … 1 or more cards from your hand." The hand revealed here
    // is the OPPONENT's, so the react fires for THEM ($opp), not the naming player. Same miss as SOR_185
    // Chimaera: the reveal was implicit in the count-the-copies loop and never announced to observers.
    if (function_exists('_SWUSec016React') && count(GetHand($opp)) > 0) {
        $savedPID = $playerID;
        _SWUSec016React($opp);
        $playerID = $savedPID;
    }
};
