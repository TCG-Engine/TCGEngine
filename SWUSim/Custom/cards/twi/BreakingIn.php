<?php
// TWI_224
// Cost 2 - Breaking In - [Cunning]
// Text: Attack with a unit. It gets +2/+0 and gains Saboteur for this attack. (When this unit attacks, ignore Sentinel and defeat the defender's Shields.)

// TWI_224 Breaking In — the chosen unit gets +2/+0 and gains Saboteur for this attack, then attacks.
$customDQHandlers["TWI_224#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $a = GetZoneObject($lastDecision);
    if (SWUObjGone($a)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);                                     // +2/+0 for this attack
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('SABOTEUR', [], SWU_DUR_ATTACK)); // Saboteur for this attack
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_224:0"] = function($player, $mzID = '') {
// Breaking In — "Attack with a unit. It gets +2/+0 and gains Saboteur for
                          // this attack."
            global $playerID;
            $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u !== null && empty($u->removed) && intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+2/+0_and_Saboteur_this_attack)", "TWI_224#0");
            return;
};
