<?php
// LAW_084
// Cost 8 - Krrsantan - Hit and Run - [Aggression,Cunning] - Power 7 - HP 7
// Text: Ambush / Overwhelm / Action [discard 2 cards from your hand]: Return this unit to your hand (from play).

// LAW_084 Krrsantan — Action handler ($unitAbilities registered after the $unitAbilities=[] init below).
$customDQHandlers["LAW_084#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction($player); return; }
    // Discard the 2 chosen cards (snapshot objects first — removing one shifts indices).
    $objs = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $objs[] = $o;
    }
    foreach ($objs as $o) { $cid = $o->CardID; $o->removed = true; SWUAddToDiscard(intval($player), $cid, 'HAND'); }
    if (!empty($objs)) DecisionQueueController::CleanupRemovedCards();
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUBounceUnit(intval($player), $mz);
    SWUAfterAction($player);
};

// LAW_084 Krrsantan — "Action [discard 2 cards from your hand]: Return this unit to your hand." No
// exhaust; the discard is a custom cost paid in the handler (availability needs 2+ hand cards).
$unitActionCostKind["LAW_084"] = 'none';

// SOR_184 Fett's Firespray — Action [2 resources]: Exhaust a non-unique unit (either player's).
// LAW_084 Krrsantan — Action [discard 2 cards from your hand]: return this unit to your hand.
$unitAbilities["LAW_084"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $hand = array_values(ZoneSearch("myHand"));
    if (count($hand) < 2) { SWUAfterAction($player); return; }
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "2|2|" . implode("&", $hand), 1, tooltip: "Discard_2_cards_to_return_Krrsantan_to_your_hand");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_084#0|{$uid}", 1);
};
