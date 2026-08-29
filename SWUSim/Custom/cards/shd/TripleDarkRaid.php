<?php
// SHD_194
// Cost 3 - Triple Dark Raid - [Cunning,Villainy]
// Text: Search the top 7 cards of your deck for a Vehicle and play it. (Put the other cards on the bottom of your deck in a random order.) It costs 5 resources less and enters play ready. Return it to its owner's hand at the end of the phase.

// ─── SHD_194 Triple Dark Raid ──────────────────────────────────────────────────
// Finalize the top-7 Vehicle search: put the non-chosen peeked cards on the bottom, then free-play the
// chosen Vehicle at -5 cost. It enters play READY ($gForceEnterReady) and carries SWU_SHD194_RETURN so it
// bounces to its owner's hand at the next regroup start. WhenPlayed auto-fires (nested-play in a CUSTOM
// continuation drains + fires exactly once — proven by SHD_242).
$customDQHandlers["SHD_194#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gForceEnterReady, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosenID = $resolved['drawn'][0] ?? null;
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if ($chosenID === null) return;                       // found nothing / declined
    AddHand(intval($player), CardID: $chosenID);
    DecisionQueueController::CleanupRemovedCards();
    $hand   = GetHand(intval($player));
    $handMz = null;
    for ($i = count($hand) - 1; $i >= 0; $i--) {
        if (empty($hand[$i]->removed) && ($hand[$i]->CardID ?? '') === $chosenID) { $handMz = "myHand-$i"; break; }
    }
    if ($handMz === null) return;
    $gForceEnterReady     = true;
    $gPlayGrantTurnEffect = 'SWU_SHD194_RETURN';
    SWUNestedPlay(intval($player), $handMz, false, 5);     // -5 cost; enters ready; return-at-regroup marker
    $gForceEnterReady = false; $gPlayGrantTurnEffect = null;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_194:0"] = function($player, $mzID = '') {
// Triple Dark Raid — "Search the top 7 cards of your deck for a Vehicle and play it.
                          // It costs 5 resources less and enters play ready. Return it to its owner's hand at
                          // the end of the phase." Search (up to 1 Vehicle); SHD_194#0 free-plays via a nested
                          // ActivateCard(discount 5) with the enters-ready + return-at-regroup grants.
            global $playerID;
            $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;
            _topDeckSearchBegin(intval($player), min(7, $deckSize),
                fn($cid) => HasTrait($cid, 'Vehicle'), "count:1", "SHD_194#0");
            return;
};
