<?php
// JTL_260
// Cost 2 - Death Star Plans - Upgrade Power 0 - Upgrade HP 0
// Text: When attached unit is attacked: The attacking player takes control of this upgrade and attaches it to a unit they control. / Attached unit gains: "The first unit you play each round costs 2 resources less."

// JTL_260 Death Star Plans (upgrade) — "When attached unit is attacked: the attacking player takes
// control of this upgrade and attaches it to a unit they control." Fired for the ATTACKER; $defenderMzID
// is the attacked host (attacker's frame). Routed through an intermediate CUSTOM so the destination
// MZCHOOSE's MZCountChoices runs under the attacker (not after DispatchTrigger restores $playerID).
$onAttackedFromUpgradeAbilities["JTL_260"] = function($attacker, $defenderMzID) {
    global $playerID;
    $playerID = intval($attacker);
    $host = GetZoneObject($defenderMzID);
    if (SWUObjGone($host)) return;
    $hostUid = intval($host->UniqueID ?? 0);
    DecisionQueueController::StoreVariable("JTL260HostUID", (string)$hostUid);
    DecisionQueueController::AddDecision($attacker, 'CUSTOM', 'JTL_260#0', 1);
};

$customDQHandlers["JTL_260#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval(DecisionQueueController::GetVariable("JTL260HostUID"));
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    // attacker's own units = destinations (they always control at least the attacker)
    $dests = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
    if (empty($dests)) return;
    SWUQueueChooseTarget(intval($player), $dests, "Attach_Death_Star_Plans_to_a_unit_you_control", "JTL_260#1");
};

$customDQHandlers["JTL_260#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval(DecisionQueueController::GetVariable("JTL260HostUID"));
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    $host = GetZoneObject($hostMz);
    if ($host === null || !is_array($host->Subcards ?? null)) return;
    // locate JTL_260's real subcard index on the host
    $subIdx = -1; $cnt = 0;
    foreach ($host->Subcards as $sub) {
        $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $isRem = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        if ($isCap || $isRem) continue;
        $scid  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if ($scid === 'JTL_260') { $subIdx = $cnt; }
        $cnt++;
    }
    // subIdx here counts only non-captive/non-removed subcards — the same index space SWUMoveUpgradeCrossUnit
    // splices on is the raw Subcards array; re-find the raw index to be safe.
    $rawIdx = -1;
    foreach ($host->Subcards as $k => $sub) {
        $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        $isRem = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if (!$isRem && $scid === 'JTL_260') { $rawIdx = $k; break; }
    }
    if ($rawIdx < 0) return;
    SWUMoveUpgradeCrossUnit($hostMz, $rawIdx, $lastDecision, intval($player));
};
