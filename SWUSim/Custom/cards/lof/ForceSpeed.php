<?php
// LOF_205
// Cost 1 - Force Speed - [Cunning,Cunning]
// Text: Attack with a unit. For this attack, it gains: "On Attack: Return any number of non-<uq> (non-unique) upgrades attached to the defender to their owners' hands."

// LOF_205 Force Speed — the chosen unit attacks with a one-shot 'LOF_205' marker that returns the
// defender's non-unique upgrades on attack (mirrors JTL_156 Trench Run's granted-On-Attack marker).
$customDQHandlers["LOF_205#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'LOF_205'); // granted On-Attack this attack (registry duration = attack)
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_205:0"] = function($player, $mzID = '') {
// Force Speed — "Attack with a unit. For this attack, it gains: 'On Attack: Return
                          // any number of non-unique upgrades attached to the defender to their owners' hands.'"
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}"; // ready units
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_unit_(returns_defender's_non-unique_upgrades)", "LOF_205#0");
            return;
};
