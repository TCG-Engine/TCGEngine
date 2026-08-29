<?php
// SEC_018
// Cost 6 - DJ - Need a Lift? - [Cunning,Cunning] - Power 4 - HP 6
// Text: Action [Exhaust]: Choose a friendly unit. If you do, play a unit from your hand. It costs 1 resource less. The chosen unit captures it. (When Played abilities resolve after the unit is captured.)
// DeployText: Saboteur / Friendly units that are rescued enter play ready.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── SEC_018 DJ ────────────────────────────────────────────────────────────────
// Action [Exhaust]: Choose a friendly unit. If you do, play a unit from your hand. It costs 1 resource
// less. The chosen unit captures it. (When Played abilities resolve AFTER the unit is captured.)
// Ordering is CORRECT: ActivateCard only QUEUES the played unit's When Played (FlushEntryTriggerBag →
// orchestration, not inline), so the SYNCHRONOUS DoCaptureUnit below runs first — the unit is captured
// (out of play) before its When Played drains, so a self-referencing When Played fizzles per CR. Same
// mechanism proven by SHD_013 Han Solo "Worth the Risk" (play a unit, deal 2 to it → unit ends with 2
// damage AND its Shielded shield, i.e. the leader's deal-2 resolves before the played unit's When Played).
$leaderAbilities["SEC_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $captors = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $captors[] = $mz;
        }
    }
    if (empty($captors)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $captors, "Choose_a_friendly_unit_to_capture_with", "SEC_018#0");
};

// Step 0: captor chosen → choose a hand unit to play (affordable at the −1 discount).
$customDQHandlers["SEC_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorMz = $lastDecision ?? '';
    $captor   = ($captorMz !== '' && str_contains($captorMz, '-')) ? GetZoneObject($captorMz) : null;
    if (SWUObjGone($captor)) { SWUAfterAction(intval($player)); return; }
    $captorUID = intval($captor->UniqueID ?? 0);
    $ready = SWUTotalPaymentCapacity(intval($player));
    $handUnits = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (max(0, SWUComputePlayCost(intval($player), $o) - 1) > $ready) continue;
        $handUnits[] = $mz;
    }
    if (empty($handUnits)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $handUnits, "Play_a_unit_from_hand_(costs_1_less)", "SEC_018#1|{$captorUID}");
};

// Step 1: play the chosen unit (−1) with a findable marker, then the captor captures it.
$customDQHandlers["SEC_018#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID  = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $handMz    = $lastDecision ?? '';
    if ($handMz === '' || !str_contains($handMz, '-')) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_018';                        // findable marker on the played unit
    SWUNestedPlay(intval($player), $handMz, false, 1);        // −1 discount; inner after-action neutralised
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('SEC_018', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    // CR 1050.3 — playing a second copy of a unique card forces its controller to defeat one of them, and
    // that defeat "occurs immediately"; it is a game rule, not a triggered ability. ActivateCard can only
    // QUEUE that choice (it is interactive), so capturing inline here would jump the queue: the capture
    // would re-index the arena underneath a pending positional offer, so picking the just-played copy
    // would silently no-op and the mandatory defeat would be SKIPPED entirely.
    // When a uniqueness choice is pending, defer the capture behind it on the same queue (same player, so
    // plain append ordering applies) and re-resolve both units by UniqueID once it has resolved. If the
    // player defeats the new copy, there is simply nothing left to capture.
    $newUID = ($newMz !== null) ? intval((GetZoneObject($newMz)->UniqueID) ?? 0) : 0;
    if ($newUID > 0 && _SWUDjUniquenessPending(intval($player))) {
        DecisionQueueController::AddDecision(intval($player), 'CUSTOM',
            "SEC_018#2|{$captorUID}|{$newUID}", 1, dontSkipOnPass: 1);
        return;   // SEC_018#2 owns the After Action
    }
    $captorMz = SWUFindMzByUID($captorUID);
    if ($newMz !== null && $captorMz !== null) DoCaptureUnit(intval($player), $captorMz, $newMz);
    SWUAfterAction(intval($player));
};

// True when ActivateCard queued the CR 1050.3 uniqueness choose-and-defeat for this player.
function _SWUDjUniquenessPending(int $player): bool {
    foreach (GetDecisionQueue($player) as $entry) {
        if (strpos(strval($entry->Param ?? ''), 'UNIQUENESS_DEFEAT') === 0) return true;
    }
    return false;
}

// Step 2: the uniqueness defeat has resolved; capture whatever survived.
$customDQHandlers["SEC_018#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID  = intval($player);
    $captorMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $newMz    = SWUFindMzByUID(intval($parts[1] ?? 0));
    // $newMz === null ⇒ the player chose to defeat the newly played copy, so there is nothing to capture.
    if ($newMz !== null && $captorMz !== null) DoCaptureUnit(intval($player), $captorMz, $newMz);
    SWUAfterAction(intval($player));
};
