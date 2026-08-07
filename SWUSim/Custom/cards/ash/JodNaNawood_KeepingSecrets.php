<?php
// ASH_219
// Cost 3 - Jod Na Nawood - Keeping Secrets - [Cunning] - Power 4 - HP 3
// Text: Sentinel / When Played: You may pay 4 resources. If you do, choose an arena. Exhaust each unit in that arena.

// ASH_219 Jod Na Nawood — Sentinel (keyword) + When Played: you may pay 4 resources. If you do, choose an
// arena. Exhaust each unit in that arena.
$whenPlayedAbilities["ASH_219:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 4) return;   // can't pay → no offer
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_4_resources_to_exhaust_each_unit_in_an_arena?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_219#0", 1);
};

$customDQHandlers["ASH_219#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES' || SWUTotalPaymentCapacity(intval($player)) < 4) return;
    SWUPayCost(intval($player), 4, 0, false);   // effect cost, not halved by JTL_105
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space", 1, tooltip: "Choose_an_arena_to_exhaust");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_219#1", 1);
};

$customDQHandlers["ASH_219#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (["my{$arena}", "their{$arena}"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) OnExhaustCard(intval($player), $mz);
        }
    }
};
