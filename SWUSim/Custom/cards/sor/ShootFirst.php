<?php
// SOR_217
// Shoot First
// Text: Attack with a unit. It gets +1/+0 for this attack and deals its combat damage before the defender. (If the defender is defeated, it deals no combat damage.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_217:0"] = function($player, $mzID = '') {
// Shoot First — "Attack with a unit. It gets +1/+0 for this attack..."
            global $playerID, $gShootFirstPending;
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (!empty($readyUnits)) {
                $gShootFirstPending = true;
                // Mandatory "attack with a unit" → auto-PASSPARAMETER when only 1 ready unit, MZCHOOSE for 2+.
                SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SHOOT_FIRST_ATTACK", 1);
            }
            return;
};
