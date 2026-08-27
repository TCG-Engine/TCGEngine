<?php
// JTL_205
// Cost 1 - Commence Patrol - [Cunning,Heroism]
// Text: Put another card in a discard pile on the bottom of its owner's deck. If you do, create an X-Wing token.

// ── JTL_205 Daring Raid — put the chosen discarded card on the bottom of its owner's deck, then create
// an X-Wing token (only fires when a card was actually put — declines no-op above). ────────────────────
$customDQHandlers["JTL_205#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $owner = SWUMzOwner((string)$lastDecision, intval($player));   // SWUMzOwner reads the seat OUT OF the mzID; the my/their ternary named seat 2 above two seats.
    $cid = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom($owner, [$cid]);
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_205:0"] = function($player, $mzID = '') {
// Daring Raid — "Put another card in a discard pile on the bottom of its owner's
                          // deck. If you do, create an X-Wing token." (may; token only on a put.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            $myD = GetDiscard(intval($player));
            // "ANOTHER card in a discard pile" — exclude THIS Commence Patrol. ActivateCard appends the
            // event to its owner's discard (SWUAddToDiscard) BEFORE dispatching this When Played, so the
            // in-flight copy is already sitting there as a live entry and was being offered: answering it
            // put Commence Patrol on the bottom of its own owner's deck and still made the X-Wing, i.e.
            // the card recycled itself into a free 2/2, which "another" forbids. (Same in-flight-event
            // family as SEC_178 Pursue the Lead / SEC_232 Kreia's Whispers, but in the DISCARD rather
            // than the hand, and NOT flagged `removed` — so the usual removed-filter does not catch it.)
            // ⚠ Only the LAST live entry is skipped, never every JTL_205: a SECOND copy already in the
            // discard is a different card and is a legal target.
            $selfIdx = -1;
            for ($i = count($myD) - 1; $i >= 0; $i--) {
                if ($myD[$i] === null || !empty($myD[$i]->removed)) continue;
                if (($myD[$i]->CardID ?? '') === 'JTL_205') $selfIdx = $i;
                break;                                   // only the most recently added live entry
            }
            for ($i = 0; $i < count($myD); $i++) { if ($i !== $selfIdx && $myD[$i] !== null && empty($myD[$i]->removed)) $targets[] = "myDiscard-{$i}"; }
            // Was GetOpponent() — NULL above seat 2, so a far-seat caster was offered NO enemy discard
            // at all — plus a literal "theirDiscard-N" that named seat 2 regardless. "a discarded card"
            // is unqualified, so it spans every live opponent's pile.
            foreach (OpponentsOf(intval($player)) as $opp) {
                $thD = GetDiscard($opp);
                for ($i = 0; $i < count($thD); $i++) { if ($thD[$i] !== null && empty($thD[$i]->removed)) $targets[] = SWUForeignMzID(intval($player), $opp, 'Discard', $i); }
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Put_a_discarded_card_on_the_bottom_of_its_owner's_deck",
                "Put_a_discarded_card_on_the_bottom_of_its_owner's_deck", "JTL_205#0");
            return;
};
