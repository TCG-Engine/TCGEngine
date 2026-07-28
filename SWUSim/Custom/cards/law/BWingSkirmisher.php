<?php
// LAW_183
// Cost 4 - B-Wing Skirmisher - [Aggression,Heroism] - Power 4 - HP 4
// Text: When Played: Deal 1 damage to each of up to 2 space units.

// LAW_183 B-Wing Skirmisher — When Played: deal 1 damage to each of up to 2 space units.
$whenPlayedAbilities["LAW_183:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $space = SWUAllUnits(null, SpaceArena);
    if (empty($space)) return;
    $k = min(2, count($space));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$k}|" . implode("&", $space), 1, tooltip: "Deal_1_to_each_of_up_to_{$k}_space_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_183#0", 1);
};

$customDQHandlers["LAW_183#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
