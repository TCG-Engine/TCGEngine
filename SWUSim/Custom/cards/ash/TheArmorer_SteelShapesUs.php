<?php
// ASH_001
// Cost 5 - The Armorer - Steel Shapes Us - [Vigilance,Command] - Power 4 - HP 6
// Text: Action [Exhaust]: Play an upgrade from your resources on a unit that entered play this phase (paying its cost). If you do, resource the top card of your deck.
// DeployText: When Attack Ends: You may play an upgrade from your resources on a friendly unit. If you do, resource the top card of your deck.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── ASH Phase 10 leaders ──────────────────────────────────────────────────────
// ASH_001 The Armorer — Action [Exhaust]: play an upgrade from your resources on a unit that entered play
// this phase (paying its cost). If you do, resource the top card of your deck. Eligible hosts = units with
// the SWU_ENTERED_PHASE_{uid} flag; resource-zone upgrades affordable + attachable to such a host.
// ⚠ ENTERED PLAY, not played (bug #1025/#1026): a leader deployed this phase is an eligible host.
// This read SWU_PLAYED_UNIT_, which ActivateCard alone sets, so deploys and tokens were excluded.
$leaderAbilities["ASH_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)
                && SWUUnitEnteredPlayThisPhase($o)) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }
    $ready     = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a play cost (CR 3.13)
    $resources = &GetResources($player);
    $targets   = [];
    $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        $cid = $resources[$i]->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Upgrade') === false) continue;
        // A card played OUT OF the resource zone may exhaust ITSELF toward its own cost: CR 6.2 pays at
        // step 4 and puts the card into play at step 5, and CR 8.22.e states it outright for Smuggle
        // ("As the card is still in the resource zone while paying costs..."). So its own ready slot is
        // part of the payable pool and nothing is subtracted here. This used to read
        // `$cost > $ready - $selfReady`, which for a READY upgrade is `$cost >= $ready` — an upgrade
        // costing EXACTLY the capacity was silently dropped from the offer and the Action soft-passed
        // (live report 2026-09-03: Armor of Fortune SEC_070 "not allowed" on an eligible host). Same
        // defect HMW_017 Osha carried (bug #976), which inherited the line from here. An EXHAUSTED
        // candidate needs no special case: it never contributed to $ready, so it cannot self-pay and
        // correctly needs the full cost from elsewhere (bug #955 stays fixed).
        if (SWUComputePlayCost($player, $resources[$i]) > $ready) continue;
        $validHosts = SWUGetUpgradeValidTargets($player, $cid);
        $ok = false;
        foreach ($hosts as $h) { if (in_array($h, $validHosts, true)) { $ok = true; break; } }
        if ($ok) $targets[] = "myResources-{$here}";
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_an_upgrade_from_resources_on_a_unit_that_entered_this_phase", "ASH_001#0");
};

$customDQHandlers["ASH_001#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    $resObj = GetZoneObject($lastDecision);
    if (SWUObjGone($resObj)) { SWUAfterAction($player); return; }
    $cardID     = $resObj->CardID ?? '';
    $validHosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)
                && SWUUnitEnteredPlayThisPhase($o)
                && in_array($mz, $validHosts, true)) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_that_entered_this_phase", "ASH_001#1|{$cardID}|{$lastDecision}");
};

$customDQHandlers["ASH_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $resMz  = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || !$hostMz || !str_contains($hostMz, '-')) { SWUAfterAction($player); return; }
    $host = GetZoneObject($hostMz);
    if (SWUObjGone($host)) { SWUAfterAction($player); return; }
    // CR 8.22.e — the card is STILL A RESOURCE while its cost is paid, so a READY upgrade exhausts
    // itself toward its own cost and only the REMAINDER comes out of the other resources. Routing it
    // through hand is a staging detail (_SWUFinalizeUpgradeAttach attaches from hand); it must not cost
    // the player an extra resource. Captured BEFORE the move, spent as $prepaid on the attach below.
    // Without this the player paid the full cost out of other resources AND lost the upgrade's slot —
    // a cost-3 upgrade cost 4 resources (live report 2026-09-03, Whistling Birds ASH_183).
    $resNow    = GetZoneObject($resMz);
    $selfPay   = (is_object($resNow) && intval($resNow->Status ?? 0) === 1) ? 1 : 0;
    $newHandMz = MZMove(intval($player), $resMz, "myHand");
    if ($newHandMz === null || $newHandMz === '-') { SWUAfterAction($player); return; }
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;
    }
    if ($handMz === '') { SWUAfterAction($player); return; }
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, $selfPay, false, false, true);
    // "If you do, resource the top card of your deck." Verify the upgrade actually landed on the host by
    // scanning its upgrades — the attach return is the TRIGGER count (0 for a vanilla upgrade), NOT a success
    // flag, so gating the ramp on it wrongly skipped the deck-resource (the deployed side already does this).
    $host2 = GetZoneObject($hostMz);
    $attached = false;
    if ($host2 !== null) {
        foreach (GetUpgradesOnUnit($host2) as $u) {
            $ucid = is_array($u) ? ($u['CardID'] ?? '') : ($u->CardID ?? '');
            if ($ucid === $cardID) { $attached = true; break; }
        }
    }
    if ($attached) {
        // The upgrade was played FROM RESOURCES (it is routed via hand only so it can't pay for itself),
        // so "when you play a card from your resources" observers fire — SEC_008 Bail Organa's heal.
        _SWUSec008HealOnResourcePlay(intval($player));
        _SWUSec245Ramp(intval($player));   // "If you do, resource the top card of your deck."
    }
    SWUAfterAction($player);
};

// ASH_001 The Armorer (deployed) — When Attack Ends: you may play an upgrade from your resources on
// a FRIENDLY unit (any, not just entered-this-phase). If you do, resource the top card of your deck.
// Combat owns the After Action (onAttackEnd), so the continuations never call SWUAfterAction.
$onAttackEndAbilities["ASH_001:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($hosts)) return;
    $ready     = SWUTotalPaymentCapacity(intval($player));
    $resources = &GetResources(intval($player));
    $targets   = []; $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        $cid = $resources[$i]->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Upgrade') === false) continue;
        // Same gate as the front side: the upgrade's own ready slot pays toward its own cost (CR 8.22.e),
        // so nothing is subtracted from the capacity here.
        if (SWUComputePlayCost(intval($player), $resources[$i]) > $ready) continue;
        $validHosts = SWUGetUpgradeValidTargets(intval($player), $cid);
        $ok = false;
        foreach ($hosts as $h) { if (in_array($h, $validHosts, true)) { $ok = true; break; } }
        if ($ok) $targets[] = "myResources-{$here}";
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Play_an_upgrade_from_your_resources?",
        "Choose_an_upgrade_from_resources", "ASH_001#2");
};

$customDQHandlers["ASH_001#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $resObj = GetZoneObject($lastDecision);
    if (SWUObjGone($resObj)) return;
    $cardID     = $resObj->CardID ?? '';
    $validHosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { if (in_array($mz, $validHosts, true)) $hosts[] = $mz; }
    }
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_friendly_unit", "ASH_001#3|{$cardID}|{$lastDecision}");
};

$customDQHandlers["ASH_001#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $resMz  = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || !$hostMz || !str_contains($hostMz, '-')) return;
    $host = GetZoneObject($hostMz);
    if (SWUObjGone($host)) return;
    // See the front side: the resource pays 1 of its own cost (CR 8.22.e).
    $resNow    = GetZoneObject($resMz);
    $selfPay   = (is_object($resNow) && intval($resNow->Status ?? 0) === 1) ? 1 : 0;
    $newHandMz = MZMove(intval($player), $resMz, "myHand");
    if ($newHandMz === null || $newHandMz === '-') return;
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;
    }
    if ($handMz === '') return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, $selfPay, false, false, true);
    // "If you do, resource the top card of your deck." Gate on the upgrade actually landing on the
    // host (the attach return is the trigger count, which is 0 for a vanilla upgrade — not a success flag).
    $host2 = GetZoneObject($hostMz);
    $attached = false;
    if ($host2 !== null) {
        foreach (GetUpgradesOnUnit($host2) as $u) {
            $uid = is_array($u) ? ($u['CardID'] ?? '') : ($u->CardID ?? '');
            if ($uid === $cardID) { $attached = true; break; }
        }
    }
    if ($attached) {
        _SWUSec008HealOnResourcePlay(intval($player));   // played from resources (see the front-side note)
        _SWUSec245Ramp(intval($player));
    }
};
