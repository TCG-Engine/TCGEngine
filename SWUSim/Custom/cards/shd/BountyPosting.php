<?php
// SHD_228
// Cost 1 - Bounty Posting - [Cunning]
// Text: Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your deck.) / You may play that upgrade (paying its cost).

// ─── SHD_228 Bounty Posting ─────────────────────────────────────────────────────
// Finalize the full-deck search: draw the found Bounty upgrade (reveal it publicly), reshuffle the rest,
// then offer "you may play that upgrade (paying its cost)" if a valid enemy host exists. The play is a
// normal upgrade play (ActivateCard at cost) whose attach flow picks an enemy unit (see the Bounty case
// in SWUGetUpgradeValidTargets); wrapped in the turn/PASS save-restore so it doesn't double-advance.
$customDQHandlers["SHD_228#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $drawnID  = $resolved['drawn'][0] ?? null;
    if ($drawnID !== null) {
        AddHand(intval($player), CardID: $drawnID);
        AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($drawnID)); // "reveal it"
    }
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);   // shuffle the deck
    if ($drawnID === null) return;                                           // no Bounty upgrade found
    if (empty(SWUGetUpgradeValidTargets(intval($player), $drawnID))) return; // no valid enemy host → can't play
    DecisionQueueController::CleanupRemovedCards();
    $hand   = GetHand(intval($player));
    $handMz = null;
    for ($i = count($hand) - 1; $i >= 0; $i--) {
        if (empty($hand[$i]->removed) && ($hand[$i]->CardID ?? '') === $drawnID) { $handMz = "myHand-$i"; break; }
    }
    if ($handMz === null) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Play_" . str_replace(' ', '_', CardTitle($drawnID)) . "?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_228#1|$handMz", 1);
};

$customDQHandlers["SHD_228#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== "YES" && $lastDecision !== "1") return;            // declined the may-play
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $handMz = $parts[0] ?? '';
    $o = GetZoneObject($handMz);
    if (SWUObjGone($o)) return;
    SWUNestedPlay(intval($player), $handMz, false, 0);   // play at cost; attach flow picks the enemy host
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_228:0"] = function($player, $mzID = '') {
// Bounty Posting — "Search your deck for a Bounty upgrade, reveal it, and draw it.
                          // (Shuffle your deck.) You may play that upgrade (paying its cost)." Full-deck search
                          // (peek all, private) for an Upgrade whose text grants a Bounty; SHD_228#0 draws it,
                          // reshuffles the rest, then offers the may-play-at-cost.
            global $playerID; $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;
            _topDeckSearchBegin(intval($player), $deckSize,
                fn($cid) => CardType($cid) === 'Upgrade' && stripos(CardText($cid) ?? '', 'Bounty') !== false,
                "count:1", "SHD_228#0");
            return;
};
