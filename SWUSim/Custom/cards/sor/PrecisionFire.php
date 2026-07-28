<?php
// SOR_168
// Cost 1 - Precision Fire - [Aggression]
// Text: Attack with a unit. It gains Saboteur for this attack. If it's a TROOPER, it also gets +2/+0 for this attack. (Ignore Sentinel and defeat the defender's Shields.)

// SOR_168 Precision Fire — chosen attacker gains Saboteur for this attack (registry GRANT_KEYWORD,
// attack duration), +2/+0 if it's a Trooper (one-shot attack bonus), then attacks. BeginSWUAttack owns
// the after-action; only the no-attacker safety path closes it.
$customDQHandlers["SOR_168#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($mz, "SOR_168");                                  // Saboteur for this attack
    if (HasTrait($obj->CardID, 'Trooper')) SWUAddAttackPowerBonus($mz, 2);
    BeginSWUAttack(intval($player), $mz);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_168:0"] = function($player, $mzID = '') {
// Precision Fire — "Attack with a unit. It gains Saboteur for this attack.
            // If it's a TROOPER, it also gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SOR_168#0");
            return;
};
