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
};
