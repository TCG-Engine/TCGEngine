<?php
// SEC_260
// Cost 2 - Inspector's Shuttle - Power 1 - HP 3
// Text: When Played: Name a card, then an opponent reveals their hand. For each copy of the named card in their hand, give an Experience token to this unit.

// SEC_260 Inspector's Shuttle — When Played: name a card; the opponent reveals their hand; for each copy
// of the named card in their hand, give an Experience token to this unit.
$whenPlayedAbilities["SEC_260:0"] = function($player, $mzID) {
    // Opponent's hand empty → nothing to reveal or count, no Experience possible → skip the naming.
    if (count(GetHand(OtherPlayer(intval($player)))) === 0) return;
    $self = GetZoneObject($mzID);
    $uid = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_260#0|{$uid}", 1);
};

$customDQHandlers["SEC_260#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $named = trim($lastDecision);
    $opp = OtherPlayer(intval($player));
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
