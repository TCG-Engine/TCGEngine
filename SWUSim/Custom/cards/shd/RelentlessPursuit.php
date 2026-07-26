<?php
// SHD_232
// Cost 3 - Relentless Pursuit - [Cunning]
// Text: Choose a friendly unit. It captures an enemy non-leader unit that costs the same as or less than it. If the friendly unit is a Bounty Hunter, give a Shield token to it. (Put the captured card facedown under the friendly unit until it leaves play.)

// ─── SHD_232 Relentless Pursuit (Event) ───────────────────────────────────────
// Choose a friendly unit. It captures an enemy non-leader unit that costs the same as or less than it.
// If the friendly unit is a Bounty Hunter, give a Shield token to it.
$customDQHandlers["SHD_232#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID  = intval($captor->UniqueID ?? 0);
    $captorCost = intval(CardCost($captor->CardID ?? ''));
    if (HasTrait($captor->CardID ?? '', 'Bounty Hunter')) DoGiveShieldToken(intval($player), $lastDecision);
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= $captorCost) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) return;   // shield still granted above; nothing to capture
    SWUQueueChooseTarget(intval($player), $enemies, "Capture_an_enemy_non-leader_unit", "SHD_232#1|{$captorUID}");
};

$customDQHandlers["SHD_232#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $captorUID = intval($parts[0] ?? 0);
    $captor    = SWUFindMzByUID($captorUID);
    if ($captor === null) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoCaptureUnit(intval($player), $captor, $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_232:0"] = function($player, $mzID = '') {
// Relentless Pursuit — "Choose a friendly unit. It captures an enemy non-leader
                          // unit that costs the same as or less than it. If the friendly unit is a Bounty
                          // Hunter, give a Shield token to it."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_capture_with", "SHD_232#0");
            return;
};
