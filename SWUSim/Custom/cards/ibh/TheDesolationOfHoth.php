<?php
// IBH_104
// Cost 6 - The Desolation of Hoth - [Vigilance]
// Text: Defeat up to 2 enemy units that each cost 3 or less.

$whenPlayedAbilities["IBH_104:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|2|" . implode("&", $targets), 1,
        tooltip:"Defeat_up_to_2_enemy_units_that_cost_3_or_less");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IBH_104#0", 1, dontSkipOnPass: 1);
};

// IBH_104 The Desolation of Hoth — defeat each selected enemy unit. Resolve UIDs first (defeating
// reindexes the arena), then defeat by re-resolved mzID.
$customDQHandlers["IBH_104#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID; $playerID = intval($player);
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
    }
};
