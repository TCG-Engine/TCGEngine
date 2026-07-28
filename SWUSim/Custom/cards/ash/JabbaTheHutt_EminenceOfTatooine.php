<?php
// ASH_042
// Cost 4 - Jabba the Hutt - Eminence of Tatooine - [Cunning,Vigilance,Villainy] - Power 2 - HP 6
// Text: Restore 2 / When Played: You may return an upgrade to its owner's hand. If it's returned to your hand, you may play it for free.

// ASH_042 Jabba the Hutt — Restore 2 (keyword) + When Played: you may return an upgrade to its owner's
// hand. If it's returned to YOUR hand, you may play it for free. Bounce mirrors SEC_200; the free replay
// reuses the LAW_245 attach machinery (_SWUFinalizeUpgradeAttach with ignoreCost).
$whenPlayedAbilities["ASH_042:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (GetUpgradesOnUnit($o) as $up) {
            $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
            $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
            if ($ucid !== '' && !$isTok) { $hosts[] = $mz; break; }
        }
    }
    if (empty($hosts)) return;
    SWUQueueMayChooseTarget(intval($player), $hosts, "Return_an_upgrade_to_its_owner's_hand?", "Choose_a_unit", "ASH_042#0");
};

$customDQHandlers["ASH_042#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = ''; $owner = 0;
    foreach (GetUpgradesOnUnit($o) as $up) {
        $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
        $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
        if ($ucid !== '' && !$isTok) {
            $cardID = $ucid;
            $owner  = is_array($up) ? intval($up['Owner'] ?? 0) : intval($up->Owner ?? 0);
            break;
        }
    }
    if ($cardID === '') return;
    SWUReturnUpgradeToHand($lastDecision, $cardID, intval($player));
    // "If it's returned to YOUR hand, you may play it for free" — only when you own the upgrade.
    if ($owner !== intval($player)) return;
    // Locate the returned upgrade in your hand and offer a valid host to attach it to (free).
    $handMz = '';
    foreach (ZoneSearch("myHand", null) as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && ($h->CardID ?? '') === $cardID) $handMz = $mz;  // last copy
    }
    if ($handMz === '') return;
    $hosts = SWUGetUpgradeValidTargets(intval($player), $cardID);
    if (empty($hosts)) return;
    SWUQueueMayChooseTarget(intval($player), $hosts, "Play_" . GameLogCardRef($cardID) . "_for_free?", "Choose_a_unit_to_attach_to", "ASH_042#1|{$cardID}|{$handMz}");
};

$customDQHandlers["ASH_042#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || !$hostMz || $hostMz === '-' || $hostMz === 'PASS' || !str_contains($hostMz, '-')) return;  // declined
    $host = GetZoneObject($hostMz);
    if (SWUObjGone($host)) return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 0, true, false, true);   // ignoreCost = free
};
