<?php
// ASH_137
// Cost 2 - Wipe Them Out - [Command]
// Text: Attack with a unit. For this attack, you may deal its excess damage to another unit in the same arena.

// ASH_137 Wipe Them Out — the chosen unit attacks with the "excess to another unit in the same arena"
// marker (consumed in SWUCollectCombatHitTriggers when there is overkill).
$customDQHandlers["ASH_137#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('ASH_137', [], SWU_DUR_ATTACK));
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_137:0"] = function($player, $mzID = '') {
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
    SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_137#0");
};
