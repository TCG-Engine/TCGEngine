<?php
// JTL_014
// Cost 6 - Admiral Trench - Chk-chk-chk-chk - [Cunning,Villainy] - Power 4 - HP 5
// Text: Action [Exhaust]: Discard a card that costs 3 or more from your hand. If you do, draw a card. / Action [3 resources, Exhaust]: If you control 6 or more resources, deploy this leader.
// DeployText: When Deployed: Reveal the top 4 cards of your deck. An opponent discards 2 of them. Draw 1 of the remaining cards and discard the other.

// ── JTL_014 Admiral Trench (leader action: discard a 3+ cost card, then draw) ────────────────────
// $lastDecision = the chosen hand card (already filtered to cost >= 3). Discard it and draw 1; the
// leader ability queued SWU_AFTER_ACTION after this to close the action.
$customDQHandlers["JTL_014#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    DoDiscardCard(intval($player), $lastDecision);
    DoDrawCard(intval($player), 1);
};

// ── JTL_014 Admiral Trench — When Deployed (deploy-side reveal flow) ─────────────────────────────
// "Reveal the top 4 cards of your deck. An opponent discards 2 of them. Draw 1 of the remaining cards
// and discard the other." Cross-player flow: stage revealed cards in the OPPONENT's TempZone for their
// pick, then in the CONTROLLER's TempZone for the draw. Each cross-player relative-mzID decision is
// queued from a CUSTOM handler (NOT inline in this trigger closure) so $playerID stays the deciding
// player at MZCountChoices time (DispatchTrigger restores $playerID after a trigger closure).
$whenPlayedAbilities["JTL_014:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // Reveal the top 4 (front non-removed) cards and pull them off the deck into a held set.
    $deck = &GetDeck(intval($player));
    $held = [];
    foreach ($deck as $c) {
        if (!empty($c->removed)) continue;
        $held[] = $c->CardID;
        $c->Remove();
        if (count($held) >= 4) break;
    }
    DecisionQueueController::CleanupRemovedCards();
    if (empty($held)) return; // empty deck — nothing to reveal (trigger-resume owns after-action)
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . implode(', ', array_map('GameLogCardRef', $held)));
    // Pass owner + the revealed CardIDs through the decision PARAM (player-agnostic) — StoreVariable is
    // scoped to the current player's store, so cross-player handlers can't read vars set under another.
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "JTL_014#1|" . intval($player) . "|" . implode(",", $held), 1);
};

// Stage the revealed cards in the OPPONENT's TempZone and queue their "discard 2" pick. Runs as a
// CUSTOM (no $playerID restore), so $playerID is left = the opponent for the MZMULTICHOOSE validation.
// $parts[0] = owner, $parts[1] = revealed CardIDs (comma-joined).
$customDQHandlers["JTL_014#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? 0);
    $held  = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    if (empty($held)) return;
    $opp   = OtherPlayer($owner);
    $temp  = &GetTempZone($opp);
    while (count($temp) > 0) array_pop($temp);
    foreach ($held as $cid) AddTempZone($opp, $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($held); $k++) $tempMZs[] = "myTempZone-" . $k;
    $discardN = min(2, count($held));
    $playerID = $opp; // leave set for MZCountChoices
    DecisionQueueController::AddDecision($opp, "MZMULTICHOOSE",
        $discardN . "|" . $discardN . "|" . implode("&", $tempMZs), 1,
        tooltip: "Discard_2_of_the_revealed_cards");
    DecisionQueueController::AddDecision($opp, "CUSTOM",
        "JTL_014#2|" . $owner . "|" . implode(",", $held), 1);
};

// Opponent's "discard 2" answer ($lastDecision = myTempZone-i&myTempZone-j). $parts[0]=owner,
// $parts[1]=revealed CardIDs. Discard the picks to the owner's discard (From DECK); stage the remaining
// cards in the OWNER's TempZone for the draw pick.
$customDQHandlers["JTL_014#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? 0);
    $held  = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    $opp   = OtherPlayer($owner);
    $pickedIdx = [];
    foreach (explode("&", (string)$lastDecision) as $mz) {
        if (preg_match('/myTempZone-(\d+)/', trim($mz), $m)) $pickedIdx[] = intval($m[1]);
    }
    $playerID = $owner;
    foreach ($pickedIdx as $idx) {
        if (isset($held[$idx])) SWUAddToDiscard($owner, $held[$idx], 'DECK');
    }
    // Drain the opponent's TempZone.
    $tmpOpp = &GetTempZone($opp);
    while (count($tmpOpp) > 0) array_pop($tmpOpp);
    DecisionQueueController::CleanupRemovedCards();
    // Remaining = held minus picked.
    $remaining = [];
    foreach ($held as $k => $cid) {
        if (!in_array($k, $pickedIdx, true)) $remaining[] = $cid;
    }
    if (empty($remaining)) return; // nothing left to draw (trigger-resume owns after-action)
    // Stage the remaining cards in the OWNER's TempZone for "draw 1, discard the other".
    $tmpOwn = &GetTempZone($owner);
    while (count($tmpOwn) > 0) array_pop($tmpOwn);
    foreach ($remaining as $cid) AddTempZone($owner, $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($remaining); $k++) $tempMZs[] = "myTempZone-" . $k;
    $playerID = $owner; // leave set for the owner's MZCHOOSE validation
    DecisionQueueController::AddDecision($owner, "MZCHOOSE", implode("&", $tempMZs), 1,
        tooltip: "Draw_1_of_the_remaining_cards_(discard_the_other)");
    DecisionQueueController::AddDecision($owner, "CUSTOM",
        "JTL_014#3|" . $owner . "|" . implode(",", $remaining), 1);
};

// Owner's "draw 1" answer ($lastDecision = myTempZone-N). $parts[0]=owner, $parts[1]=remaining CardIDs.
// Draw the chosen card to hand; discard the rest of the remaining cards (From DECK). Drain TempZone.
$customDQHandlers["JTL_014#3"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $owner = intval($parts[0] ?? $player);
    $playerID = $owner;
    $remaining = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    $drawIdx = -1;
    if (preg_match('/myTempZone-(\d+)/', trim((string)$lastDecision), $m)) $drawIdx = intval($m[1]);
    foreach ($remaining as $k => $cid) {
        if ($k === $drawIdx) {
            AddHand($owner, CardID: $cid);
            AddGameLogEntry('DRAW', 'P' . $owner . ' drew a card');
        } else {
            SWUAddToDiscard($owner, $cid, 'DECK');
        }
    }
    $tmpOwn = &GetTempZone($owner);
    while (count($tmpOwn) > 0) array_pop($tmpOwn);
    DecisionQueueController::CleanupRemovedCards();
};

// JTL_014 Admiral Trench — Leader Action [Exhaust]: Discard a card that costs 3 or more from your hand.
// If you do, draw a card. (The deployed-side "When Deployed" reveal/discard/draw ability is handled
// separately.) Mandatory discard if an eligible card exists; the draw rides the JTL_014 continuation.
$leaderAbilities["JTL_014"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (ZoneSearch("myHand") as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) >= 3) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no 3+ cost card → action spent
    SWUQueueChooseTarget($player, $targets, "Discard_a_card_costing_3_or_more", "JTL_014#0", may: true);
    SWUQueueAfterAction($player);
};
