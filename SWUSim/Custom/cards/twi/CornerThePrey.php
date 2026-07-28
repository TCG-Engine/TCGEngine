<?php
// TWI_139
// Cost 1 - Corner the Prey - [Aggression,Villainy]
// Text: Attack with a unit. It gets +1/+0 for this attack for each damage on the defender at the start of this attack.

// TWI_139 Corner the Prey — the chosen attacker gets +1/+0 per defender damage (applied in
// ExecuteSWUAttack via the TWI_139 attack-marker), then attacks.
$customDQHandlers["TWI_139#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $a = GetZoneObject($lastDecision);
    if (SWUObjGone($a)) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('TWI_139', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_139:0"] = function($player, $mzID = '') {
// Corner the Prey — "Attack with a unit. It gets +1/+0 for this attack for each
                          // damage on the defender at the start of this attack."
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
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+1/+0_per_damage_on_the_defender)", "TWI_139#0");
            return;
};
