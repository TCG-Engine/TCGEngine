<?php
// SHD_128  |  Reprints: TWI_123
// Cost 1 - Outflank - [Command]
// Text: Attack with 2 units (one at a time).

// ─── SHD_128 Outflank (Event) — attack with a second unit after the first ─────
$customDQHandlers["SHD_128#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,0,{$uid}");   // not-rebel, mandatory, +0, exclude self, any arena
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_128:0"] = function($player, $mzID = '') {
// Outflank — "Attack with 2 units (one at a time)."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                $arr = GetZone($z);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
                    if (_SWUUnitHardCantAttack($u)) continue;  // JTL_059 "can't attack" etc. aren't legal Outflank attackers
                    $units[] = "{$z}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_the_first_unit_to_attack_with", "SHD_128#0");
            return;
};
