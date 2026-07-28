<?php
// TWI_172
// Cost 2 - Grim Resolve - [Aggression]
// Text: Attack with a non-leader unit. It gains Grit for this attack. (It gets +1/+0 for each damage on it.)

// TWI_172 Grim Resolve — the chosen non-leader unit gains Grit for this attack, then attacks.
$customDQHandlers["TWI_172#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $a = GetZoneObject($lastDecision);
    if (SWUObjGone($a)) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('GRIT', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_172:0"] = function($player, $mzID = '') {
// Grim Resolve — "Attack with a non-leader unit. It gains Grit for this attack."
            global $playerID;
            $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u !== null && empty($u->removed) && intval($u->Status) === 1 && !IsLeaderUnit($u)) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_non-leader_unit_(it_gains_Grit_for_this_attack)", "TWI_172#0");
            return;
};
