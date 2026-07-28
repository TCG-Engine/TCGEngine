<?php
// ASH_184
// Cost 1 - Follow Me - [Aggression]
// Text: Attack with a unit. After completing the attack, give 3 Advantage tokens to a unit.

// ASH_184 Follow Me (event) — the chosen unit attacks; the "give 3 Advantage tokens to a unit" rider is
// armed as an attack-duration marker (ASH_184) consumed in CollectAfterAttackTriggers (fires even if the
// attacker is defeated). Combat owns the after-action; on no-valid-attacker the event already fizzled.
$customDQHandlers["ASH_184#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('ASH_184', [], SWU_DUR_ATTACK));
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_184:0"] = function($player, $mzID = '') {
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
    SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_184#0");
};
