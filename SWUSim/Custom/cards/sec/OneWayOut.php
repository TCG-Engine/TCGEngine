<?php
// SEC_157
// Cost 1 - One Way Out - [Aggression,Heroism]
// Text: Attack with a unit. It gets +1/+0 and gains Overwhelm for this attack. If it attacks a unit, the defender loses all abilities for this attack.

// SEC_157 One Way Out (event) — the chosen attacker gets +1/+0 and Overwhelm for this attack; the
// SEC_157 signal marker makes the DEFENDING unit lose all abilities (applied in CollectCombatStep1Triggers).
$customDQHandlers["SEC_157#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($mz, 1);                                         // +1/+0 for this attack
    AddTurnEffect($mz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'SEC_157')); // gains Overwhelm this attack
    AddTurnEffect($mz, SWUMakeTurnEffect('SEC_157', [], SWU_DUR_ATTACK));   // signal: defender loses abilities
    BeginSWUAttack(intval($player), $mz);     // combat owns SWUAfterAction
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_157:0"] = function($player, $mzID = '') {
// One Way Out — Attack with a unit. It gets +1/+0 and gains Overwhelm for this
                          // attack. If it attacks a unit, the defender loses all abilities for this attack.
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
            SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with", "SEC_157#0");
            return;
};
