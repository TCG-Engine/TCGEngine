<?php
// SHD_088
// Cost 5 - Ephant Mon - Head of Security - [Command,Villainy] - Power 4 - HP 6
// Text: On Attack: Choose an enemy non-leader unit that attacked your base this phase. A friendly unit in the same arena captures that unit. (Put the captured card facedown under the friendly unit until the friendly unit leaves play.)

// ─── SHD_088 Ephant Mon ───────────────────────────────────────────────────────
// On Attack: Choose an enemy non-leader unit that attacked your base this phase. A friendly unit in the
// same arena captures that unit. (Uses the per-unit SWU_DEALT_BASEDMG flag set in combat.)
$onAttackAbilities["SHD_088:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "attacked YOUR base" is read on the CASTER'S OWN seat — see SWUUnitAttackedMyBaseThisPhase.
    // This used to read SWU_DEALT_BASEDMG_{uid} on each candidate's controller, which answers only
    // "damaged A base": at 3+ seats a unit that hit a THIRD player's base became capturable by you, and
    // a 0-power attacker (damage-gated flag) could not be captured at all.
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (SWUUnitAttackedMyBaseThisPhase(intval($player), $o)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Capture_an_enemy_that_attacked_your_base?", "Choose_an_enemy_unit", "SHD_088#0");
};

$customDQHandlers["SHD_088#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $friendlyZone = (strpos((string)$lastDecision, 'GroundArena') !== false) ? 'myGroundArena' : 'mySpaceArena';
    $captors = [];
    foreach (ZoneSearch($friendlyZone, AnyUnitFilter) as $mz) {
        $c = GetZoneObject($mz);
        if ($c !== null && empty($c->removed)) $captors[] = $mz;
    }
    if (empty($captors)) return;   // no friendly captor in that arena
    SWUQueueChooseTarget(intval($player), $captors, "Choose_a_friendly_unit_to_capture_with", "SHD_088#1|" . intval($o->UniqueID ?? 0));
};

$customDQHandlers["SHD_088#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $targetMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($targetMz === null) return;
    DoCaptureUnit(intval($player), $lastDecision, $targetMz);
};
