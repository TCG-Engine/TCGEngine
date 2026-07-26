<?php
// JTL_038
// Cost 5 - Corvus - Inferno Squadron Raider - [Vigilance,Villainy] - Power 4 - HP 5
// Text: Restore 2 / When Played: You may attach a friendly Pilot unit or upgrade to this unit. (Defeat all upgrades on that Pilot and remove all damage from it.)

// ── JTL_038 Corvus — When Played: You may attach a friendly Pilot unit to this. (Defeat all upgrades on
// that Pilot and remove all damage from it.) Offers friendly Pilot UNITS (either arena); the chosen unit
// becomes a Pilot upgrade on Corvus via SWUMoveUnitToUpgrade (normal upgrades defeated, damage cleared,
// captives carried). Param carries Corvus's mzID. (Pilot-UPGRADE relocation from another vehicle: TODO.)
$whenPlayedAbilities["JTL_038:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            if ($mz === $mzID) continue; // not Corvus itself
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            // A friendly Pilot UNIT, or a friendly Vehicle that HOSTS a pilot upgrade (relocate that pilot).
            if (HasTrait($o->CardID, 'Pilot') || _SWUFindPilotSubcard($o) !== null) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Attach_a_friendly_Pilot_to_this", "Attach_a_friendly_Pilot_to_this", "JTL_038#0|" . $mzID);
};

$customDQHandlers["JTL_038#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $corvusMz = $parts[0] ?? '';
    $chosen = GetZoneObject($lastDecision);
    $corvus = GetZoneObject($corvusMz);
    if (SWUObjGone($chosen) || SWUObjGone($corvus)) return;
    $pilotIdx = _SWUFindPilotSubcard($chosen);
    if ($pilotIdx !== null) {
        SWURelocatePilotSubcard($lastDecision, $pilotIdx, $corvusMz); // chose a Vehicle → move its pilot upgrade
    } else {
        SWUMoveUnitToUpgrade($lastDecision, $corvusMz, true);          // chose a Pilot unit → it becomes the upgrade
    }
};
