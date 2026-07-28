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
    $ready = SWUResourceCount(intval($player), readyOnly: true);
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
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, false, 1);        // −1 discount; inner after-action neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('SEC_018', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    $captorMz = SWUFindMzByUID($captorUID);
    if ($newMz !== null && $captorMz !== null) DoCaptureUnit(intval($player), $captorMz, $newMz);
    SWUAfterAction(intval($player));
};
