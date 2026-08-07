<?php
// JTL_013
// Cost 5 - Poe Dameron - I Can Fly Anything - [Aggression,Heroism] - Power 4 - HP 6 - Upgrade Power 2 - Upgrade HP 1
// Text: Action [1 resource, Exhaust]: Flip this leader and attach him as an upgrade to a friendly Vehicle unit without a Pilot on it.
// DeployText: / Action [1 resource]: Attach this upgrade to a friendly Vehicle unit without a Pilot on it. Use this ability only once each round. /
// Epic Action: If you control 5 or more resources, deploy this leader (as a unit).

// JTL_013 — receives the MZCHOOSE host mzID from JTL_013's leader Action 2+-vehicle path.
// Called only when there are ≥2 eligible Vehicles and the player picks one.
$customDQHandlers["JTL_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostMz = $lastDecision ?? '';
    if ($hostMz === '' || $hostMz === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $hostMz, 0, true, true);
};

$unitActionCostKind["JTL_013"] = 'none';

$unitActionResourceCosts["JTL_013"] = 1;

$unitAbilities["JTL_013"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);

    // Once-per-round guard.
    if (!SWUHasUseAvailable(SWUGetLeader(intval($player)))) {
        SWUAfterAction($player);
        return;
    }

    // Collect eligible hop targets: friendly Vehicles with 0 pilots, excluding the current host.
    $all = array_merge(
        ZoneSearch("myGroundArena", ["Unit", "Leader Unit"]),
        ZoneSearch("mySpaceArena",  ["Unit", "Leader Unit"])
    );
    $targets = array_values(array_filter($all, function($mz) use ($mzID) {
        if ($mz === $mzID) return false; // exclude the current host
        $hostObj = GetZoneObject($mz);
        if (SWUObjGone($hostObj)) return false;
        if (!HasTrait($hostObj->CardID ?? '', 'Vehicle')) return false;
        return SWUVehiclePilotCount($hostObj) === 0;
    }));

    if (empty($targets)) {
        SWUAfterAction($player);
        return;
    }

    // Splice JTL_013 out of the current host's Subcards (no defeat — it moves, not dies).
    $currentHost = GetZoneObject($mzID);
    if ($currentHost !== null && is_array($currentHost->Subcards ?? null)) {
        foreach ($currentHost->Subcards as $key => $sub) {
            $subCardID = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            $isRemoved = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
            if (!$isRemoved && $subCardID === 'JTL_013') {
                array_splice($currentHost->Subcards, $key, 1);
                break;
            }
        }
    }

    // Mark once-per-round used BEFORE queuing the attach (so it's set even if we auto-attach).
    SWUConsumeUse(SWUGetLeader(intval($player))); // once/round hop via leader NumUses

    if (count($targets) === 1) {
        // Auto-attach to the single eligible Vehicle — route through the chokepoint.
        _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $targets[0], 0, true, true);
        return;
    }

    // 2+ vehicles: let the player pick. Store the decision, then finalize.
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
        tooltip: 'Choose_a_Vehicle_to_hop_Poe_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_013#1', 1);
};

// JTL_013#1 (was POE_013_HOP) — receives the MZCHOOSE host mzID, finalizes the hop attach.
$customDQHandlers["JTL_013#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostMz = $lastDecision ?? '';
    if ($hostMz === '' || $hostMz === '-') {
        SWUAfterAction(intval($player));
        return;
    }
    _SWUFinalizeUpgradeAttach(intval($player), 'JTL_013', '', $hostMz, 0, true, true);
};

// JTL_013 Poe Dameron — Leader Action [1 resource, Exhaust]:
// "Flip this leader and attach him as an upgrade to a friendly Vehicle unit without a Pilot on it."
// The handler flips the leader (Deployed = true), pays the resource (already exhausted by
// SWULeaderAction), picks the Vehicle, and attaches via _SWUFinalizeUpgradeAttach (isPilot=true).
// The host does NOT become a Leader Unit — JTL_013 is not in CardLeaderCanDeployAsUpgrade.
// EpicActionUsed is NOT set — this is a leader Action, not the Epic-Action deploy threshold.
// Affordability (≥1 friendly 0-pilot Vehicle, ≥1 ready resource) is gated in SWULeaderActionAffordable.
$leaderAbilities["JTL_013"] = function(int $player): void {
    global $playerID;
    $playerID = $player;


    // Flip the leader to its deployed side (but NOT the epic-action threshold deploy).
    // Twin Suns: mutate the JTL_013 leader specifically (a seat may hold two leaders); leader CardIDs
    // are unique per seat. Fall back to first live for a single-leader game.
    $ldr = SWUFindLeaderByCardID($player, 'JTL_013');
    if ($ldr === null) $ldr = SWUGetLeaderByIndex($player, 0);
    if ($ldr !== null) {
        $ldr->Deployed        = true;
        $ldr->DeployedUniqueID = 0; // attached as subcard, no standalone arena UID
    }

    $vehicles = SWUGetPoe013AttachVehicles($player);
    if (empty($vehicles)) {
        SWUAfterAction($player);
        return;
    }

    if (count($vehicles) === 1) {
        // Auto-attach to the single eligible Vehicle — no picker needed.
        _SWUFinalizeUpgradeAttach($player, 'JTL_013', '', $vehicles[0], 0, true, true);
        return;
    }

    // 2+ eligible Vehicles: let the player pick.
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $vehicles), 1,
        tooltip: 'Choose_a_Vehicle_to_attach_Poe_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_013#0', 1);
};
