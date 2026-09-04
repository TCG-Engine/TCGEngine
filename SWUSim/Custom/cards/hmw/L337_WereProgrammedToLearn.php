<?php
// HMW_215
// Cost 6 - L3-37, We're Programmed to Learn - [Cunning][Heroism] - Unit (Ground) 5/7 - unique
// Traits: Underworld, Droid
// Text: When you play an event that costs 3 or less: You may play it again from your discard pile for
//       free. Use this ability only once each phase.
//
// A REACTIVE OBSERVER, not a trigger window — there is no ability stub for it. The observation is
// registered in SWUCollectOwnPlayReactions (GameLogic, beside SOR_182 Bossk), which is the shared
// "when YOU play a card" collector reached from all three play sites. Two things come from there for
// free: the "costs 3 or less" test reads the PRINTED cost (an off-aspect cost-3 event billed at 5 still
// qualifies), and the $allowedUIDs snapshot means a unit the event itself put into play is not an
// observer of that event.
//
// ⚠ RECURSION. The replayed copy is ITSELF "an event you played that costs 3 or less", so the
// once-each-phase flag has to be set BEFORE the replay or L3-37 observes his own replay and the card
// loops. The collector's flag check is therefore load-bearing twice over: as the phase limit, and as
// the recursion guard.
//
// ⚠ THE LIMIT IS PER COPY (CR 885). The flag is keyed by this L3-37's UniqueID, so a bounced-and-
// replayed L3-37 is a NEW COPY with a fresh use — which is what makes the A New Adventure + Lady Proxima
// loop legal. Keying it per PLAYER would have been a rules bug that killed the combo.
//
// ⚠ CONSUME ON USE, NOT ON TRIGGER. The flag is set on the YES branch only, so declining does not burn
// the phase's use and a later cheap event can still offer (the standing house ruling for once-per-round
// "may" reactions). Cleared in RegroupPhaseStart beside its siblings.

// ── The reaction: "you may play it again" ─────────────────────────────────────────────────────────
// $playedCardID arrives in the dispatcher's mzID slot — FlushTriggerBag drops extraParams, so that slot
// is the established channel for a trigger payload (LAW_141, LAW_201).
if (!function_exists('Hmw215ReplayEventReaction')) {
    function Hmw215ReplayEventReaction(int $player, string $playedCardID): void {
        global $playerID;
        $playerID = intval($player);
        // "<playedCardID>,<observing L3-37's UniqueID>" — comma-delimited because the dispatcher
        // pipe-splits its own param. The UID rides on so the handler can spend THAT COPY's use.
        $bits   = explode(',', $playedCardID);
        $evCard = (string)($bits[0] ?? '');
        $selfUid = intval($bits[1] ?? 0);
        if ($evCard === '') return;
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
            tooltip: "Play_" . str_replace(' ', '_', (string)CardTitle($evCard)) . "_again_for_free?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM",
            "HMW_215#0|" . $evCard . "|" . $selfUid, 1);
    }
}

// ── The replay ────────────────────────────────────────────────────────────────────────────────────
$customDQHandlers["HMW_215#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision !== 'YES') return;          // a decline leaves the use unspent

    // ⚠ $parts holds the args AFTER the handler name, so the payload is index 0, not 1.
    $cardID  = (string)($parts[0] ?? '');
    $selfUid = intval($parts[1] ?? 0);
    if ($cardID === '') return;

    // The just-played event is already sitting in its owner's discard — ActivateCard files it there
    // BEFORE dispatching, which is why this reaction can reach it at all. Take the LAST live entry with
    // that CardID: that is the copy just played, and an older copy of the same event stays untouched.
    // Raw array index, matching what SWUPlayFromDiscard hands to ActivateCard.
    $discard = GetDiscard(intval($player));
    $idx = -1;
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if (($discard[$i]->CardID ?? '') === $cardID) $idx = $i;
    }
    if ($idx < 0) return;

    // Set the flag BEFORE the play: the replay is itself a cheap event play and would otherwise be
    // observed by this very ability. See the recursion note at the top.
    AddGlobalEffects(intval($player), 'SWU_HMW215_USED_' . $selfUid);

    // "for FREE" — ignoreCost waives the resource cost including the aspect penalty (the standing
    // play-for-free ruling). SWUNestedPlay, never a bare ActivateCard: the outer event play already owns
    // an after-action, and the nested frame is what stops the second one becoming a free extra action.
    SWUNestedPlay(intval($player), "myDiscard-{$idx}", true);
};
