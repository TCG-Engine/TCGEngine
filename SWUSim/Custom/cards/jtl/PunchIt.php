<?php
// JTL_231
// Cost 1 - Punch It - [Cunning]
// Text: Attack with a Vehicle unit. It gets +2/+0 for this attack.

// ── JTL_231 Punch It — chosen Vehicle gets +2/+0, then attacks. ───────────────────────────────────────
$customDQHandlers["JTL_231#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_231:0"] = function($player, $mzID = '') {
// Punch It — "Attack with a Vehicle unit. It gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
                    if (HasTrait($u->CardID, 'Vehicle')) $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_Vehicle_unit_to_attack_with", "JTL_231#0");
            return;
};
