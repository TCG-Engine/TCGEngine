<?php
// JTL_227
// Cost 2 - Superheavy Ion Cannon - [Cunning] - Upgrade Power 0 - Upgrade HP 3
// Text: Attach to a Capital Ship or Transport unit. / Attached unit gains: "On Attack: You may exhaust a non-leader unit the defending player controls. If you do, deal indirect damage equal to its power to that player."

// ── JTL_227 Superheavy Ion Cannon — granted On Attack: may exhaust an enemy non-leader unit; if you do,
// deal indirect damage equal to its power to that player. ─────────────────────────────────────────────
$onAttackAbilities["JTL_227:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_values(array_merge(
        ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
        ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter)
    ));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_an_enemy_unit_(deal_indirect_equal_to_its_power)", "Choose_an_enemy_non-leader_unit", "JTL_227#0");
};

$customDQHandlers["JTL_227#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $pow = max(0, intval(ObjectCurrentPower($obj)));
    $obj->Status = 0;   // exhaust the enemy unit
    if ($pow > 0) SWUDealIndirectDamage(intval($player), $pow, OtherPlayer(intval($player)));
};
