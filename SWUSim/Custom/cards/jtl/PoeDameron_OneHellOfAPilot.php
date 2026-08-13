<?php
// JTL_100
// Cost 4 - Poe Dameron - One Hell of a Pilot - [Command,Heroism] - Power 3 - HP 3 - Upgrade Power 2 - Upgrade HP 3
// Text: When played as a unit: Create an X-Wing token. You may attach this unit as an upgrade to a friendly Vehicle unit without a Pilot on it. / Piloting [2 resources Command Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.)

// No-op WhenPlayedAsUpgrade handler: prevents the fallback to WhenPlayed when
// JTL_100 is played as a pilot (Piloting keyword path).
$whenPlayedAsUpgradeAbilities["JTL_100:0"] = function($player, $mzID) {
    // Intentional no-op: the "When played as a unit" clause must NOT fire
    // when JTL_100 is attached via its Piloting keyword.
};

// WhenPlayed handler: fires only when JTL_100 enters play as a unit.
$whenPlayedAbilities["JTL_100:0"] = function($player, $mzID) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    // Step 1 — create the X-Wing token (JTL_T02) unconditionally. JTL_T02 is a Space unit (2/2);
    // SWUCreateUnitToken fires no WhenPlayed triggers (tokens aren't "played") but does apply
    // Shielded-on-create (e.g. JTL_047 Yularen granting Shielded to Vehicles).
    SWUCreateUnitToken(intval($player), 'JTL_T02');

    // Step 2 — collect free-attach targets: friendly Vehicles with 0 pilots (strict rule).
    $vehicles = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter),
        ZoneSearch("mySpaceArena",  AnyUnitFilter)
    );
    $targets = array_values(array_filter($vehicles, function($vMz) use ($mzID) {
        $obj = GetZoneObject($vMz);
        if (SWUObjGone($obj)) return false;
        if ($vMz === $mzID) return false; // exclude JTL_100 itself
        if (!HasTrait($obj->CardID ?? '', 'Vehicle')) return false;
        return SWUPilotCanAttach('JTL_100', $obj, 'freeattach');
    }));

    if (!empty($targets)) {
        // Snapshot JTL_100's UniqueID so the continuation can re-resolve it after attach.
        $poeObj = GetZoneObject($mzID);
        $poeUID = intval($poeObj->UniqueID ?? 0);
        SWUQueueMayChooseTarget(
            intval($player),
            $targets,
            "You_may_attach_Poe_Dameron_to_a_friendly_Vehicle",
            "Choose_a_friendly_Vehicle_without_a_Pilot",
            "JTL_100#0|{$poeUID}"
        );
    }

    $playerID = $savedPID;
};

// JTL_100 free-attach continuation: receives the chosen Vehicle mzID.
// Declines ("-") → no-op (JTL_100 stays as unit, token already in play).
// Accept → remove JTL_100 from its arena and attach as Pilot subcard on the vehicle.
$customDQHandlers["JTL_100#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $hostMz  = $lastDecision;   // chosen Vehicle mzID from MZMAYCHOOSE
    $poeUID  = intval($parts[0] ?? 0);

    // Re-resolve JTL_100 by its UniqueID (index may have shifted if other enters happened).
    $poeMz = ($poeUID > 0) ? SWUFindMzByUID($poeUID) : null;
    if ($poeMz === null) {
        // JTL_100 is no longer in play (e.g. removed by an effect between queue and resolve).
        $playerID = $savedPID;
        return;
    }

    // Validate the host still exists and still has 0 pilots (strict rule).
    $hostObj = GetZoneObject($hostMz);
    if (SWUObjGone($hostObj)) {
        $playerID = $savedPID;
        return;
    }
    if (!SWUPilotCanAttach('JTL_100', $hostObj, 'freeattach')) {
        $playerID = $savedPID;
        return;
    }

    // Attach JTL_100 as a Pilot subcard (ignoreCost=true — the free-attach has no cost).
    // _SWUFinalizeUpgradeAttach removes JTL_100 from the arena ($poeMz) and adds it as
    // a subcard on $hostMz with IsPilot=true.
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_100', $poeMz, $hostMz, 0, true, true);
    $playerID = $savedPID;
};
