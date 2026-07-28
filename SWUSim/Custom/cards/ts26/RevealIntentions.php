<?php
// TS26_80
// Cost 1 - Reveal Intentions - [Cunning]
// Text: Each player reveals their hand. / In player order, each player discards a card from the hand of the player to their right. Then, each player draws a card.

// TS26_80 Reveal Intentions — the deciding player ($player) discards the chosen card from their right
// neighbor's (opponent's) hand. The chosen mzID is "theirHand-N" relative to the decider's frame, so set
// $playerID = decider before resolving it. Used by BOTH discards (caster's and opponent's).
$customDQHandlers["TS26_80#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') return;
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    $opp    = OtherPlayer(intval($player));   // hand owner = the decider's right neighbor
    $cardID = $obj->CardID;
    $obj->Remove();
    SWUAddToDiscard($opp, $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
};

// "Then, each player draws a card." — resolves after both discards.
$customDQHandlers["TS26_80#1"] = function($player, $parts, $lastDecision) {
    $P = intval($parts[0] ?? 0); $O = intval($parts[1] ?? 0);
    if ($P > 0) DoDrawCard($P, 1);
    if ($O > 0) DoDrawCard($O, 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_80:0"] = function($player, $mzID = '') {
    global $playerID; $savedPID = $playerID;
    $P = intval($player); $O = OtherPlayer($P);
    // "Each player reveals their hand" — public reveal.
    foreach ([$P, $O] as $rp) {
        $refs = [];
        foreach (GetHand($rp) as $c) { if (empty($c->removed)) $refs[] = GameLogCardRef($c->CardID); }
        AddGameLogEntry('REVEAL', "P{$rp} revealed their hand: " . (empty($refs) ? '(empty)' : implode(', ', $refs)), 'ALL');
        if (!empty($refs) && function_exists('_SWUSec016React')) _SWUSec016React($rp);
    }
    $playerID = $P;
    $oHand = ZoneSearch("theirHand");
    if (!empty($oHand)) SWUQueueChooseTarget($P, $oHand, "Discard_a_card_from_the_opponent's_hand", "TS26_80#0");
    $playerID = $O;
    $pHand = ZoneSearch("theirHand");
    if (!empty($pHand)) SWUQueueChooseTarget($O, $pHand, "Discard_a_card_from_the_opponent's_hand", "TS26_80#0");
    $playerID = $savedPID;
    DecisionQueueController::AddDecision($P, "CUSTOM", "TS26_80#1|{$P}|{$O}", 1);
};
