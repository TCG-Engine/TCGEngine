<?php
// ASH_001
// Cost 5 - The Armorer - Steel Shapes Us - [Vigilance,Command] - Power 4 - HP 6
// Text: Action [Exhaust]: Play an upgrade from your resources on a unit that entered play this phase (paying its cost). If you do, resource the top card of your deck.
// DeployText: When Attack Ends: You may play an upgrade from your resources on a friendly unit. If you do, resource the top card of your deck.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── What a resource card can be played as, and on which hosts ─────────────────────────────────────
// Official ruling (The Armorer - Steel Shapes Us, 07/21/2026): "You can use The Armorer's ability to play any card
// that can be played as an upgrade, including Pilots." A Pilot played through a "play an upgrade" ability can ONLY be
// played as an upgrade (Piloting ruling, 03/06/2025). Both sides used to filter by printed type 'Upgrade'; a Pilot is
// printed as a Unit, so it was never offered and the Action soft-passed (live report 2026-10-06, after ASH_110 Admiral
// Ackbar played the space Vehicles it should have landed on).
// Returns [isPilot, hosts]: the friendly units the card may legally attach to that the player can pay for, priced the
// way the attach will charge it (a Pilot at its Piloting cost, on a Vehicle with room — SWUGetPilotValidTargets). The
// card's own ready slot is part of the payment capacity (CR 8.22.e), so nothing is subtracted for it. Hosts are [] for
// a card that is neither an upgrade nor a Pilot, or that the player cannot afford. Same shape as A Fine Addition's
// _SWUTwi040HostsFor, which already plays Pilots as upgrades.
function _SWUAsh001HostsFor(int $player, $resObj): array {
    $cid = (string)($resObj->CardID ?? '');
    if ($cid === '') return [false, []];
    if (CardPilotingCost($cid) !== null) return [true, SWUGetPilotValidTargets($player, $cid)];
    if (strpos(CardType($cid) ?? '', 'Upgrade') === false) return [false, []];
    if (SWUComputePlayCost($player, $resObj) > SWUTotalPaymentCapacity($player)) return [false, []];
    return [false, SWUGetUpgradeValidTargets($player, $cid)];
}

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
    $resources = &GetResources($player);
    $targets   = [];
    $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        // An upgrade OR a Pilot, affordable, with its legal hosts (_SWUAsh001HostsFor). A card played OUT OF the
        // resource zone may exhaust ITSELF toward its own cost: CR 6.2 pays at step 4 and puts the card into play
        // at step 5, and CR 8.22.e states it outright for Smuggle ("As the card is still in the resource zone while
        // paying costs..."). So its own ready slot is part of the payable pool and nothing is subtracted. This used
        // to read `$cost > $ready - $selfReady`, which for a READY upgrade is `$cost >= $ready` — an upgrade costing
        // EXACTLY the capacity was silently dropped from the offer and the Action soft-passed (live report
        // 2026-09-03: Armor of Fortune SEC_070 "not allowed" on an eligible host). Same defect HMW_017 Osha carried
        // (bug #976), which inherited the line from here. An EXHAUSTED candidate needs no special case: it never
        // contributed to the capacity, so it cannot self-pay and correctly needs the full cost (bug #955 stays fixed).
        [, $validHosts] = _SWUAsh001HostsFor($player, $resources[$i]);
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
    [$isPilot, $validHosts] = _SWUAsh001HostsFor(intval($player), $resObj);
    $pilotFlag  = $isPilot ? '1' : '0';
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
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_that_entered_this_phase", "ASH_001#1|{$cardID}|{$lastDecision}|{$pilotFlag}");
};

$customDQHandlers["ASH_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID  = $parts[0] ?? '';
    $resMz   = $parts[1] ?? '';
    $isPilot = ($parts[2] ?? '0') === '1';   // a Pilot attaches as a Pilot, priced at its Piloting cost
    $hostMz  = $lastDecision ?? '';
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
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, $selfPay, false, $isPilot, true);
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
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): a teammate's unit is friendly,
    // so this pool is SWUFriendlyUnits(). ⚠ NOT SWUControlledUnits() — "a unit you control", an
    // ability COST, and "attack with" all stay 'my'. Degrades to 'my' outside a team game.
    $hosts = SWUFriendlyUnits(null, AnyUnitFilter);
    if (empty($hosts)) return;
    $resources = &GetResources(intval($player));
    $targets   = []; $pos = 0;
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        $here = $pos; $pos++;
        // Same gate as the front side: an upgrade OR a Pilot, priced against the full capacity (its own ready
        // slot pays toward its own cost, CR 8.22.e).
        [, $validHosts] = _SWUAsh001HostsFor(intval($player), $resources[$i]);
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
    [$isPilot, $validHosts] = _SWUAsh001HostsFor(intval($player), $resObj);
    $pilotFlag  = $isPilot ? '1' : '0';
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { if (in_array($mz, $validHosts, true)) $hosts[] = $mz; }
    }
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_friendly_unit", "ASH_001#3|{$cardID}|{$lastDecision}|{$pilotFlag}");
};

$customDQHandlers["ASH_001#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID  = $parts[0] ?? '';
    $resMz   = $parts[1] ?? '';
    $isPilot = ($parts[2] ?? '0') === '1';
    $hostMz  = $lastDecision ?? '';
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
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $handMz, $hostMz, $selfPay, false, $isPilot, true);
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
