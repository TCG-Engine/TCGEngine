<?php
// JTL_123
// Cost 1 - Dogfight - [Command]
// Text: Attack with a unit, even if it's exhausted. That unit can't attack bases for this attack.

// ── JTL_123 Dogfight — attack with the chosen unit (even if exhausted); it can't attack bases. ────────
$customDQHandlers["JTL_123#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    BeginSWUAttack(intval($player), $lastDecision, true);   // noBases = true (can't attack bases this attack)
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_123:0"] = function($player, $mzID = '') {
// Dogfight — "Attack with a unit. It can attack even if it's exhausted. It
                          // can't attack bases for this attack." (Candidates include exhausted units.)
            global $playerID;
            $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    $units[] = "{$zone}-{$i}";   // ready OR exhausted
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with_(can't_attack_bases)", "JTL_123#0");
            return;
};
