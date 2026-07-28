<?php
// JTL_261
// Cost 1 - Attack Run
// Text: Attack with 2 space units (one at a time).

// ── JTL_261 Attack Run — chosen space unit attacks, then a chained second space unit attacks. ──────────
$customDQHandlers["JTL_261#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,0,{$uid},space");  // not-rebel, mandatory, +0, exclude self, space only
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_261:0"] = function($player, $mzID = '') {
// Attack Run — "Attack with 2 space units (one at a time)."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
                $units[] = "mySpaceArena-{$i}";
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_the_first_space_unit_to_attack_with", "JTL_261#0");
            return;
};
