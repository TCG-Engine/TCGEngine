<?php
// SEC_179
// Cost 3 - Aggressive Negotiations - [Aggression]
// Text: Attack with a unit. For this attack, it gets +1/+0 for each card in your hand.

// SEC_179 Aggressive Negotiations (event) — Attack with a unit. For this attack, it gets +1/+0 for
// each card in your hand. (The event itself has already left hand, so count the current hand.)
$customDQHandlers["SEC_179#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    $handCount = 0; foreach (GetHand(intval($player)) as $c) { if (empty($c->removed)) $handCount++; }
    if ($handCount > 0) SWUAddAttackPowerBonus($mz, $handCount);
    BeginSWUAttack(intval($player), $mz);   // combat owns SWUAfterAction
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_179:0"] = function($player, $mzID = '') {
// Aggressive Negotiations — Attack with a unit. For this attack, it gets +1/+0
                          // for each card in your hand.
            global $playerID; $playerID = intval($player);
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
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SEC_179#0");
            return;
};
