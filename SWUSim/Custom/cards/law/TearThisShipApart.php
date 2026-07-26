<?php
// LAW_066
// Cost 7 - Tear This Ship Apart - [Command,Cunning,Villainy]
// Text: Look at all of an opponent's resources. You may play 1 of those cards for free. If you do, that opponent resources the top card of their deck.

$customDQHandlers["LAW_066#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    if (SWUDecisionDeclined($lastDecision)) return; // declined → no play, no refill
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $type   = CardType($cardID);
    if (strpos($type, 'Upgrade') !== false) {
        // Pick a valid host (auto-resolves if exactly one); attach + refill happen in #1.
        $hosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
        if (empty($hosts)) return;
        SWUQueueChooseTarget(intval($player), $hosts, "Attach_the_stolen_upgrade", "LAW_066#1|{$opp}|{$lastDecision}|{$cardID}");
        return;
    }
    if (_SWUPlayForeignResourceFree(intval($player), $opp, $lastDecision, $cardID, $type)) {
        TearThisShipApartRefill($opp); // "If you do, that opponent resources the top card of their deck."
    }
};

$customDQHandlers["LAW_066#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp    = intval($parts[0] ?? OtherPlayer(intval($player)));
    $resMz  = $parts[1] ?? '';
    $cardID = $parts[2] ?? '';
    if (SWUDecisionDeclined($lastDecision)) return;
    AddGlobalEffects(intval($player), 'SWU_CARDS_PLAYED');
    AddGameLogEntry('PLAY', 'P' . intval($player) . ' played ' . GameLogCardRef($cardID) . " from P{$opp}'s resources for free");
    // Attach the foreign upgrade to the chosen host for free (suppress the After Action — the LAW_066 event owns it).
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $resMz, $lastDecision, 0, true, false, true);
    TearThisShipApartRefill($opp);
};

// ── LAW_066 Tear This Ship Apart — foreign-owned, any-type free play from an opponent's resources ──
// Mirrors SWUPlayFromOpponentDiscard for units (Owner:opp, Controller:caster); plays events under the
// caster (card → the OWNER's discard); attaches upgrades free to a caster host. The opponent then
// resources their deck-top (ready), netting their resource count unchanged. (Continuations queued by
// the LAW_066 OnPlayEvent case in CardEffects.php.)
function TearThisShipApartRefill(int $opp): void
{
  // "resources the top card of their deck" — the plain "resource" verb, so it enters EXHAUSTED
  // (readied at the controller's next ReadyPhase), mirroring LAW_029 / the regroup resource step.
  // NOT ready: only effects that say "as a ready resource" (SEC_242) enter ready.
  global $playerID;
  $savedPID = $playerID;
  $playerID = $opp;
  $deck = ZoneSearch("myDeck", null); // opp's deck, in the opp frame
  if (!empty($deck))
    SWURampResourceExhausted($opp, $deck[0]);
  $playerID = $savedPID;
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_066:0"] = function($player, $mzID = '') {
// Tear This Ship Apart — "Look at all of an opponent's resources. You may play
                          // 1 of those cards for free. If you do, that opponent resources the top card of
                          // their deck." The look-at is the theirResources MZMAYCHOOSE (GetNextTurn reveals
                          // those resources to the chooser while it's pending). Offer only PLAYABLE cards
                          // (skip Credit tokens; an upgrade only if a valid host exists).
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $allRes = ZoneSearch("theirResources", null);
            // "Look at ALL of an opponent's resources" — log the (non-Credit) resources looked at, so it's
            // scrollable later. Credit tokens are already public, so they're excluded from the reveal log.
            SWULogResourceReveal(intval($player), array_values(array_filter($allRes,
                fn($mz) => !SWUIsCreditToken(GetZoneObject($mz)->CardID ?? ''))));
            $offer = [];
            foreach ($allRes as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $cid = $o->CardID;
                if (SWUIsCreditToken($cid)) continue;                 // a Credit token can't be "played"
                if (SWUCardPlayBlocked(intval($player), $cid)) continue; // SOR_062 named-card lock
                if (strpos(CardType($cid), 'Upgrade') !== false
                        && empty(SWUGetUpgradeValidTargets(intval($player), $cid))) continue; // no host
                $offer[] = $mz;
            }
            if (empty($offer)) return; // looked, but nothing playable → fizzle (no refill)
            SWUQueueMayChooseTarget(intval($player), $offer,
                "Play_one_of_the_opponent's_resources_for_free?", "Choose_a_card_to_play",
                "LAW_066#0|{$opp}");
            return;
};
