<?php
// SOR_252
// Cost 1 - Restock
// Text: Choose up to 4 cards in a discard pile. Put them on the bottom of their owner's deck in a random order.

// SOR_252 Restock — move the chosen discard cards to the bottom of their owner's deck (random order).
$customDQHandlers["SOR_252#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    // "Choose up to 4 cards in A discard pile" — ONE pile. The flat MZMULTICHOOSE can't narrow
    // dynamically, so enforce here: the FIRST pick fixes the pile and picks from the other pile are
    // ignored (server-side rules enforcement; the UI pool legitimately shows both piles because the
    // first pick is what chooses the pile).
    $byOwner = [1 => [], 2 => []];
    $pilePrefix = null;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $prefix = (strpos($mz, 'my') === 0) ? 'my' : 'their';
        if ($pilePrefix === null) $pilePrefix = $prefix;
        elseif ($prefix !== $pilePrefix) continue;   // cross-pile pick → not a legal choice, skip
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $owner = ($prefix === 'my') ? intval($player) : GetOpponent(intval($player));
        $byOwner[$owner][] = $o->CardID;
        $o->removed = true;
    }
    DecisionQueueController::CleanupRemovedCards();
    foreach ($byOwner as $owner => $ids) {
        if (!empty($ids)) _topDeckPutRemainingToBottom($owner, $ids);   // shuffles → bottom of deck
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_252:0"] = function($player, $mzID = '') {
// Restock — choose up to 4 cards in a discard pile; bottom of owner's deck (random).
            global $playerID;
            $playerID = intval($player);
            $cards = [];
            $myD = GetDiscard($player);
            for ($i = 0; $i < count($myD); $i++) {
                if ($myD[$i] !== null && empty($myD[$i]->removed)) $cards[] = "myDiscard-{$i}";
            }
            // Was GetOpponent() — NULL above seat 2, so a far-seat caster saw no enemy discard at all —
            // plus a literal "theirDiscard-N" naming seat 2 regardless of whose card it was.
            foreach (OpponentsOf($player) as $opp) {
                $thD = GetDiscard($opp);
                for ($i = 0; $i < count($thD); $i++) {
                    if ($thD[$i] !== null && empty($thD[$i]->removed)) $cards[] = SWUForeignMzID(intval($player), $opp, 'Discard', $i);
                }
            }
            if (empty($cards)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|4|" . implode("&", $cards), 1, "Choose_up_to_4_cards_for_deck_bottom");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_252#0", 1);
            return;
};
