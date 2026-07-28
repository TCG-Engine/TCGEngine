<?php
// JTL_124
// Cost 1 - Tandem Assault - [Command]
// Text: Attack with a space unit. If you do, attack with a ground unit, and that ground unit gets +2/+0 for this attack.

// ── JTL_124 Tandem Assault — the chosen space unit attacks, then a ground unit (+2/+0) attacks. ────────
$customDQHandlers["JTL_124#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,2,{$uid},ground"); // not-rebel, mandatory, +2, exclude self, ground only
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_124:0"] = function($player, $mzID = '') {
// Tandem Assault — "Attack with a space unit. Then, attack with a ground unit.
                          // It gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $spaceUnits = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if (SWUObjGone($u)) continue;
                if (intval($u->Status) === 1) $spaceUnits[] = "mySpaceArena-{$i}";
            }
            if (empty($spaceUnits)) return;
            SWUQueueChooseTarget(intval($player), $spaceUnits, "Choose_a_space_unit_to_attack_with", "JTL_124#0");
            return;
};
