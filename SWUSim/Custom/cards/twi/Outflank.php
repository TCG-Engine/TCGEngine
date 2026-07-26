<?php
// TWI_123
// Outflank
// Text: Attack with 2 units (one at a time).

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_123:0"] = function($player, $mzID = '') {
// Outflank — "Attack with 2 units (one at a time)." (Reprint of SHD_128.)
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
