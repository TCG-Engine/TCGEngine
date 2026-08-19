<?php
// SEC_003
// Cost 6 - Lama Su - We Modified Their Genetics - [Vigilance,Villainy] - Power 3 - HP 7
// Text: Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle unit. It costs 1 resource less. If you do, deal 1 damage to that unit.
// DeployText: When this unit completes an attack (and survives): You may play an upgrade from your discard pile on a friendly non-Vehicle unit. It costs 1 resource less.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SEC_003 Lama Su (deployed) — When this unit completes an attack (and survives): You may play an
// upgrade from your discard pile on a friendly non-Vehicle unit. It costs 1 resource less. Combat owns
// the After Action (onAttackEnd), so no continuation here calls SWUAfterAction.
$onAttackEndAbilities["SEC_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready    = SWUTotalPaymentCapacity(intval($player));
    $upgrades = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
        $hosts = _SWUSec003Hosts(intval($player), $o->CardID);
        if (empty($hosts)) continue;
        $cost = max(0, SWUComputePlayCost(intval($player), $o, GetZoneObject($hosts[0])) - 1);
        if ($cost <= $ready) $upgrades[] = $mz;
    }
    if (empty($upgrades)) return;     // nothing playable → no offer
    SWUQueueMayChooseTarget(intval($player), $upgrades,
        "Play_an_upgrade_from_your_discard_(costs_1_less)?", "Choose_an_upgrade", "SEC_003#2");
};

// Step 2: discard-upgrade chosen — pick the non-Vehicle host.
$customDQHandlers["SEC_003#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $upMz = $lastDecision ?? '';
    if ($upMz === '' || $upMz === '-' || $upMz === 'PASS') return;   // declined
    $up = str_contains($upMz, '-') ? GetZoneObject($upMz) : null;
    if (SWUObjGone($up)) return;
    $cardID = $up->CardID;
    $hosts  = _SWUSec003Hosts(intval($player), $cardID);
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_friendly_non-Vehicle_unit", "SEC_003#3|{$cardID}|{$upMz}");
};

// Step 3: host chosen — attach from discard at the −1 discount (no deal-damage rider on the deploy side).
$customDQHandlers["SEC_003#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    $host   = ($hostMz !== '' && str_contains($hostMz, '-')) ? GetZoneObject($hostMz) : null;
    if ($cardID === '' || SWUObjGone($host)) return;
    // prepaid=1 = "costs 1 resource less"; suppress After Action (combat owns it on onAttackEnd).
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 1, false, false, true);
};

// Leader Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle unit. It costs 1
// resource less. If you do, deal 1 damage to that unit. (Gate ensures a playable upgrade exists.)
$leaderAbilities["SEC_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $upgrades = _SWUSec003PlayableHandUpgrades($player);
    if (empty($upgrades)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $upgrades, "Play_an_upgrade_from_your_hand_(costs_1_less)", "SEC_003#0");
};

// Step 0: an upgrade was chosen — pick the non-Vehicle host.
$customDQHandlers["SEC_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $upMz = $lastDecision ?? '';
    $up   = ($upMz !== '' && str_contains($upMz, '-')) ? GetZoneObject($upMz) : null;
    if (SWUObjGone($up)) { SWUAfterAction(intval($player)); return; }
    $cardID = $up->CardID;
    $hosts  = _SWUSec003Hosts(intval($player), $cardID);
    if (empty($hosts)) { SWUAfterAction(intval($player)); return; }
    // The chosen host TAKES 1 DAMAGE as part of the same clause — a downside on your own unit that the
    // prompt never mentioned, so a 1-HP host could be picked and killed by its own upgrade.
    SWUQueueChooseTarget(intval($player), $hosts,
        "Choose_a_friendly_non-Vehicle_unit_to_attach_it_to_(that_unit_takes_1_damage)",
        "SEC_003#1|{$cardID}|{$upMz}");
};

// Step 1: host chosen — attach (−1 via prepaid) then deal 1 to that unit, then close the action.
$customDQHandlers["SEC_003#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    $host   = ($hostMz !== '' && str_contains($hostMz, '-')) ? GetZoneObject($hostMz) : null;
    if ($cardID === '' || SWUObjGone($host)) { SWUAfterAction(intval($player)); return; }
    $hostUID = intval($host->UniqueID ?? 0);
    // prepaid=1 applies "costs 1 resource less"; suppress the helper's own After Action (we own it).
    $triggered = _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 1, false, false, true);
    // "If you do, deal 1 damage to that unit." Re-resolve the host by UID (the attach kept it in place).
    $hMz = SWUFindMzByUID($hostUID);
    if ($hMz !== null) SWUDealDamageToUnit($hMz, 1, intval($player));
    if ($triggered === 0) SWUAfterAction(intval($player));   // a triggered upgrade's own flush owns the close
};
