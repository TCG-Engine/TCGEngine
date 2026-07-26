<?php
// JTL_096
// Cost 3 - Blue Leader - Scarif Air Support - [Command,Heroism] - Power 3 - HP 3
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may pay 2 resources. If you do, move this unit to the ground arena and give 2 Experience tokens to it. (It's a ground unit.)

// JTL_096 Blue Leader — Ambush (keyword) + When Played: You may pay 2 resources. If you do, move this
// unit to the ground arena and give it 2 Experience tokens. (It becomes a ground unit.)
$whenPlayedAbilities["JTL_096:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    if (SWUResourceCount(intval($player), true) < 2) return; // can't pay → no offer
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1,
        tooltip: "Pay_2_to_move_Blue_Leader_to_the_ground_arena_with_2_Experience?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_096#0|' . $uid, 1);
};

$customDQHandlers["JTL_096#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return;
    $uid = intval($parts[0] ?? 0);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUPayCost(intval($player), 2, 0, false);   // effect cost ("you may pay 2..."), not halved by JTL_105
    $newMz = SWUMoveUnitBetweenArenas($mz, 'GroundArena');
    if ($newMz === '') return;
    for ($i = 0; $i < 2; $i++) DoGiveExperienceToken(intval($player), $newMz);
};
