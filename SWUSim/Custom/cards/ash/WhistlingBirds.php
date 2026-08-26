<?php
// ASH_183
// Cost 3 - Whistling Birds - [Aggression] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "When Attack Ends: If this unit dealt combat damage to an opponent's base, deal 2 damage to each unit that opponent controls in this unit's arena."

// ASH_183 Whistling Birds (upgrade) — host gains "When Attack Ends: if this unit dealt combat damage to
// an opponent's base, deal 2 to each unit that opponent controls in this unit's arena." OnAttackEndFromUpgrade.
// ⚠ TWO FUNNELS. The MAIN attack path deliberately SKIPS this registration (CollectAfterAttackTriggers
// `continue`s on ASH_183) and runs its own inlined copy from $combatCtx, so the ability still fires when
// the host died mid-attack. This registration serves the OTHER dispatcher (SWUFireOnAttackEndFromUpgrade).
// Both must scope to the DEFENDING seat — fixing only one leaves the reported bug alive on the other.
$onAttackEndFromUpgradeAbilities["ASH_183"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    if (intval(GetSWUVar('SWU_LAST_ATTACKER_BASEHIT', '0')) <= 0) return;   // didn't hit a base
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    $arena = strpos($hostMzID, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
    // Snapshot enemy UIDs in the host's arena, then deal 2 to each.
    $uids = [];
    // "each unit THAT OPPONENT controls" — "that opponent" is the seat whose base was just damaged,
    // already determined by the attack. "their{$arena}" fans out over EVERY live opponent above two
    // seats, so one base hit sprayed all three opponents' boards (reported 2026-08-25).
    $defSeat = SWUCurrentDefendingSeat(intval($player));
    foreach (ZoneSearch(SWUSeatZone(intval($player), $defSeat, $arena), AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
