<?php
// ASH_090
// Cost 2 - Reforge - [Vigilance]
// Text: Defeat an upgrade on a friendly unit. If you do, search the top 8 cards of your deck for an upgrade that can attach to that unit, reveal it, and play it on that unit. It costs 4 resources less.

// ASH_090 Reforge — after defeating the friendly upgrade (DefeatUpgThen gives the host mzID), search the
// top 8 for an upgrade that can attach to that host, then play it on the host at -4 (ASH_090#1).
$customDQHandlers["ASH_090#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $hostMz = $parts[0] ?? '';   // DefeatUpgThen passes the host mzID
    $host = ($hostMz && str_contains($hostMz, '-')) ? GetZoneObject($hostMz) : null;
    if (SWUObjGone($host)) return;
    $hostUID = intval($host->UniqueID ?? 0);
    if ($hostUID <= 0) return;
    // Only offer upgrades that (a) can attach to the host AND (b) the player can pay for at the −4 price.
    // SWUGetUpgradeValidTargets is called with a null upgrade object (host-restriction only, no built-in
    // affordability gate), so affordability is checked separately here against the host-specific cost
    // minus 4 — mirroring the prepaid=4 attach at resolve. Without this the UI offered unaffordable
    // upgrades that then got stuck in hand when the (reduced) cost couldn't be paid.
    $ready = SWUTotalPaymentCapacity(intval($player));
    _topDeckSearchBegin(intval($player), 8,
        function($cid) use ($player, $hostUID, $ready) {
            if (strpos(CardType($cid) ?? '', 'Upgrade') === false) return false;
            $hMz = SWUFindMzByUID($hostUID);
            if ($hMz === null || !in_array($hMz, SWUGetUpgradeValidTargets(intval($player), $cid), true)) return false;
            $host = GetZoneObject($hMz);
            return max(0, SWUComputePlayCost(intval($player), (object)['CardID' => $cid], $host) - 4) <= $ready;
        },
        "count:1", "ASH_090#1|{$hostUID}");
};

$customDQHandlers["ASH_090#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $hostUID  = intval($parts[0] ?? 0);
    $allIDs   = array_values(array_filter(explode(',', $parts[1] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen   = $resolved['drawn'];
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if (empty($chosen)) return;
    $cardID = $chosen[0];
    $hostMz = SWUFindMzByUID($hostUID);
    if ($hostMz === null) return;
    // Stage the chosen upgrade into hand, then attach it to the host for cost − 4 (prepaid).
    AddHand(intval($player), CardID: $cardID);
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;
    }
    if ($handMz === '') return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, 4, false, false, true);   // prepaid 4 = "costs 4 less"
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_090:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            foreach (GetUpgradesOnUnit($o) as $up) {
                $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
                if (!$isTok) { $hosts[] = $mz; break; }
            }
        }
    }
    if (empty($hosts)) return;   // no friendly upgrade to defeat → fizzle
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_090#0");
    if (count($hosts) === 1) DecisionQueueController::AddDecision(intval($player), "PASSPARAMETER", $hosts[0], 1);
    else DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_an_upgrade_on_a_friendly_unit");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
};
