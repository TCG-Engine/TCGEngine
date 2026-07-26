<?php
// SHD_106
// Cost 4 - Rule with Respect - [Command,Heroism]
// Text: A friendly unit captures each enemy non-leader unit that attacked your base this phase.

// ─── SHD_106 Rule with Respect (a friendly unit captures each base-attacker) ──
$customDQHandlers["SHD_106#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $captorMz = $lastDecision;   // DoCaptureUnit takes the captor as a mzID STRING
    $captor = GetZoneObject($captorMz);
    if (SWUObjGone($captor)) return;
    $uids = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $uid = intval($o->UniqueID ?? 0);
            if ($uid > 0 && GlobalEffectCount(intval($o->Controller ?? 0), 'SWU_DEALT_BASEDMG_' . $uid) > 0) $uids[] = $uid;
        }
    }
    foreach ($uids as $uid) {
        $emz = SWUFindMzByUID($uid);   // mzID string
        if ($emz === null) continue;
        $eo = GetZoneObject($emz);
        if ($eo !== null && empty($eo->removed)) DoCaptureUnit(intval($player), $captorMz, $emz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_106:0"] = function($player, $mzID = '') {
// Rule with Respect — "A friendly unit captures each enemy non-leader unit that
                          // attacked your base this phase." (SWU_DEALT_BASEDMG_{uid} marks base-attackers.)
            global $playerID; $playerID = intval($player);
            $anyAttacker = false;
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    if (GlobalEffectCount(intval($o->Controller ?? 0), 'SWU_DEALT_BASEDMG_' . intval($o->UniqueID ?? 0)) > 0) { $anyAttacker = true; break 2; }
                }
            }
            if (!$anyAttacker) return;
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_capture_the_base_attackers", "SHD_106#0");
            return;
};
