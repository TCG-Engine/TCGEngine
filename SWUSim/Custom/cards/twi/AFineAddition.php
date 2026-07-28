<?php
// TWI_040
// Cost 0 - A Fine Addition - [Vigilance,Villainy]
// Text: If an enemy unit was defeated this phase, play an upgrade from your hand or from any player's discard pile, ignoring its aspect penalty.

// Step 0: an upgrade was chosen ($lastDecision = its source mzID). Determine pilot-vs-upgrade + valid
// hosts, stash the source mz + pilot flag, and offer the host choice.
$customDQHandlers["TWI_040#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return; // declined the "may"
    global $playerID; $playerID = intval($player);
    $up = GetZoneObject($lastDecision);
    if (SWUObjGone($up)) return;
    $cid = $up->CardID ?? '';
    $isPilot = false;
    $hosts = _SWUTwi040HostsFor(intval($player), $cid, $isPilot);
    if ($hosts === null) return; // host disappeared between the two steps
    DecisionQueueController::StoreVariable("TWI040_UP", $lastDecision . '|' . $cid . '|' . ($isPilot ? '1' : '0'));
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_to_attach_it_to", "TWI_040#1");
};

// Step 1: a host was chosen ($lastDecision = host mzID). Attach the stashed upgrade (aspect waived), via
// the DIRECT attach helper (bypasses ActivateCard). The event owns the After Action → suppress it here.
$customDQHandlers["TWI_040#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $stash = DecisionQueueController::GetVariable("TWI040_UP");
    if (!$stash) return;
    [$upMz, $cid, $pilotFlag] = array_pad(explode('|', $stash), 3, '');
    $isPilot = ($pilotFlag === '1');
    // Owner for CR-correct discard routing: a card played from the OPPONENT's discard is still owned by
    // the opponent (it returns to their discard when it leaves play).
    $srcObj = GetZoneObject($upMz);
    $origOwner = ($srcObj !== null) ? intval($srcObj->Owner ?? $player) : intval($player);
    if ($origOwner <= 0) $origOwner = intval($player);
    $GLOBALS['gTwi040IgnoreAspect'] = true;
    _SWUFinalizeUpgradeAttach(intval($player), $cid, $upMz, $lastDecision, 0, false, $isPilot, true);
    $GLOBALS['gTwi040IgnoreAspect'] = false;
    // Fix ownership on the just-attached subcard if it came from the opponent's discard.
    if ($origOwner !== intval($player)) {
        $host = GetZoneObject($lastDecision);
        if ($host !== null && is_array($host->Subcards ?? null)) {
            for ($i = count($host->Subcards) - 1; $i >= 0; $i--) {
                $sc = $host->Subcards[$i];
                if (($sc->CardID ?? '') === $cid) { $sc->Owner = $origOwner; break; }
            }
        }
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_040:0"] = function($player, $mzID = '') {
// A Fine Addition — "If an enemy unit was defeated this phase, play an upgrade
                          // from your hand or from any player's discard pile, ignoring its aspect penalty."
            global $playerID; $playerID = intval($player);
            // Condition: you defeated an enemy unit this phase (SWU_ENEMY_DEFEATED, cleared at RGS).
            if (GlobalEffectCount(intval($player), 'SWU_ENEMY_DEFEATED') <= 0) return; // condition unmet → fizzle
            // A Fine Addition is still a removed-but-uncompacted entry in its caster's hand right now, so
            // compact first — else a hand candidate's myHand-N index is offset by the stale slot (LOF_150/
            // SOR_167 gotcha) and the chosen mzID resolves to the wrong card.
            DecisionQueueController::CleanupRemovedCards();
            // Collect playable upgrade/pilot candidates from hand + both discard piles (each with a valid
            // host and affordable with the aspect penalty waived). Pilots qualify — A Fine Addition plays
            // from a KNOWN zone (no "search for an upgrade" clause that pilots can't be found by), so a
            // Piloting card can be played as an upgrade here (user-confirmed ruling; unlike Reforge).
            $cands = _SWUTwi040Candidates(intval($player));
            if (empty($cands)) return; // nothing playable → fizzle
            // "may" pick which upgrade (or decline). Attach happens via _SWUFinalizeUpgradeAttach (a DIRECT
            // attach path — it does NOT route through SWUBeginPlayCard/ActivateCard, so the old nested-play
            // no-op doesn't apply). The event's FINISH_PLAY_CARD owns the After Action (suppressed below).
            SWUQueueMayChooseTarget(intval($player), $cands,
                "Play_an_upgrade_(A_Fine_Addition)?", "Choose_an_upgrade_to_play", "TWI_040#0");
            return;
};
