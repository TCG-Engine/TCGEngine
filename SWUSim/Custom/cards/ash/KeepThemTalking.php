<?php
// ASH_233
// Cost 2 - Keep Them Talking - [Cunning]
// Text: Exhaust up to 2 units that each cost 3 or less.

// ASH_233 Keep Them Talking — continuation: exhaust the chosen ≤3-cost units.
$customDQHandlers["ASH_233#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) OnExhaustCard(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_233:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    $max = min(2, count($tg));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Exhaust_up_to_2_units_that_cost_3_or_less");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_233#0", 1);
};
