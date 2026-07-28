<?php
// SHD_184
// Cost 2 - Bazine Netal - Spy for the First Order - [Cunning,Villainy] - Power 1 - HP 3
// Text: When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that player draws a card. / Smuggle [4 resources Cunning Villainy]

// ─── SHD_184 Bazine Netal ─────────────────────────────────────────────────────
// When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that
// player draws a card. (Her old listing in the JTL_111 draw-reaction hook was a mis-mapping —
// removed.)
$whenPlayedAbilities["SHD_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWULookAtOpponentHand(intval($player));   // logs the private reveal, returns theirHand-N
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Discard_1_of_those_cards?", "Discard_1_of_those_cards?", "SHD_184#0");
};

$customDQHandlers["SHD_184#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $opp    = OtherPlayer(intval($player));
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
    DoDrawCard($opp, 1);   // "If you do, that player draws a card."
};
