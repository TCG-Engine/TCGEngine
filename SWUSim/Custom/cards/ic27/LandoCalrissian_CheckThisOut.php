<?php
// IC27_167
// Cost 3 - Lando Calrissian - Check This Out - [Cunning,Cunning] - Unit (Ground) 4/4 (unique)
//   Trait: Official
// Text: When Played: Return 3 friendly resources to their owner's hands. Then, you may resource up to
//       3 cards from your hand.
//
// Two halves, and only the SECOND is optional. The return is mandatory and exact-3 (fewer only when
// fewer exist); the "then" half is "up to 3", so declining to 0 is legal and must still leave the
// return applied. The halves are independent — with no resources to return the resourcing still
// happens, and with an empty hand the return still happens.

// Offer the second half. Split out so it can be reached both after a return and when there was
// nothing to return at all.
function Ic27167OfferResourcing(int $player): void {
    global $playerID; $playerID = intval($player);
    $hand = [];
    foreach (ZoneSearch('myHand', null) as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) $hand[] = $mz;
    }
    if (empty($hand)) return;
    $max = min(3, count($hand));
    DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE',
        "0|{$max}|" . implode('&', $hand), 1, tooltip: "Resource_up_to_3_cards_from_your_hand");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'IC27_167#1', 1);
    // $playerID intentionally left = $player: MZCountChoices resolves the relative mzIDs after return.
}

$whenPlayedAbilities["IC27_167:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $res = [];
    foreach (ZoneSearch('myResources', null) as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) $res[] = $mz;
    }
    $n = min(3, count($res));
    if ($n <= 0) {                       // nothing to return — the "then" half still resolves
        Ic27167OfferResourcing(intval($player));
        return;
    }
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
        "{$n}|{$n}|" . implode('&', $res), 1,
        tooltip: "Return_3_friendly_resources_to_their_owners_hands");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_167#0', 1);
};

$customDQHandlers["IC27_167#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picks = SWUDecisionDeclined($lastDecision) ? [] : explode('&', $lastDecision);
    // Return in DESCENDING index order: each returned resource is marked removed and the zone is
    // compacted below, so acting low-index-first would shift the picks still to be processed.
    usort($picks, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
    foreach ($picks as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        // SWUReturnResourceToHand routes to the resource's OWNER (defaulting to the controller when
        // Owner is unset), so a controlled-but-enemy-owned resource goes back to THEIR hand.
        SWUReturnResourceToHand(intval($player), $mz);
    }
    DecisionQueueController::CleanupRemovedCards();
    Ic27167OfferResourcing(intval($player));
};

$customDQHandlers["IC27_167#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // "up to 3" includes zero
    $picks = explode('&', $lastDecision);
    usort($picks, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
    foreach ($picks as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        // Enters EXHAUSTED — the same rule as the regroup resource step (readied at the next ready step).
        SWURampResourceExhausted(intval($player), $mz);
    }
};
