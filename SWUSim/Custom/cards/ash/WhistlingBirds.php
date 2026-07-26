<?php
// ASH_183
// Cost 3 - Whistling Birds - [Aggression] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "When Attack Ends: If this unit dealt combat damage to an opponent's base, deal 2 damage to each unit that opponent controls in this unit's arena."

// ASH_183 Whistling Birds (upgrade) — host gains "When Attack Ends: if this unit dealt combat damage to
// an opponent's base, deal 2 to each unit that opponent controls in this unit's arena." OnAttackEndFromUpgrade.
$onAttackEndFromUpgradeAbilities["ASH_183"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    if (intval(GetSWUVar('SWU_LAST_ATTACKER_BASEHIT', '0')) <= 0) return;   // didn't hit a base
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    $arena = strpos($hostMzID, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
    // Snapshot enemy UIDs in the host's arena, then deal 2 to each.
    $uids = [];
    foreach (ZoneSearch("their{$arena}", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    }
};
