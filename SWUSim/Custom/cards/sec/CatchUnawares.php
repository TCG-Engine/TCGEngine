<?php
// SEC_229
// Cost 2 - Catch Unawares - [Cunning]
// Text: Attack with a unit. The defender gets -4/-0 for this attack.

// SEC_229 Catch Unawares (event) — Attack with a unit. The defender gets -4/-0 for this attack.
// Mark the chosen attacker with the SWU_DEF_DEBUFF_4 one-shot (SWUCombatDamage reads it), then attack.
$customDQHandlers["SEC_229#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($mz, 'SWU_DEF_DEBUFF_4');   // the defender it picks gets -4/-0 for this attack
    BeginSWUAttack(intval($player), $mz);     // combat owns SWUAfterAction
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_229:0"] = function($player, $mzID = '') {
// Catch Unawares — Attack with a unit. The defender gets -4/-0 for this attack.
            global $playerID; $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with_(defender_-4/-0)", "SEC_229#0");
            return;
};
