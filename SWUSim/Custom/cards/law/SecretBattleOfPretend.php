<?php
// LAW_226
// Cost 2 - Secret Battle of Pretend - [Cunning,Heroism]
// Text: Exhaust a friendly unit. If you do, for each different aspect it has, exhaust an enemy unit in the same arena.

// LAW_226 Secret Battle of Pretend — exhaust the chosen friendly unit, then for each different aspect
// it has, exhaust an enemy unit in the same arena (player chooses which).
$customDQHandlers["LAW_226#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnExhaustCard(intval($player), $lastDecision);
    $aspects = array_filter(array_map('trim', explode(',', (string)(CardAspect($o->CardID ?? '') ?? ''))));
    $n = count(array_unique($aspects));
    if ($n <= 0) return;
    $isSpace = (strpos($lastDecision, 'Space') !== false);
    $enemyZone = $isSpace ? "theirSpaceArena" : "theirGroundArena";
    $enemies = [];
    foreach (ZoneSearch($enemyZone, AnyUnitFilter) as $mz) {
        $u = GetZoneObject($mz);
        if ($u !== null && empty($u->removed) && intval($u->Status ?? 0) === 1) $enemies[] = $mz;  // ready (exhaustable)
    }
    if (empty($enemies)) return;
    $k = min($n, count($enemies));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "{$k}|{$k}|" . implode("&", $enemies), 1, tooltip: "Exhaust_{$k}_enemy_unit(s)_in_the_same_arena");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_226#1", 1);
};

$customDQHandlers["LAW_226#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        OnExhaustCard(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_226:0"] = function($player, $mzID = '') {
// Secret Battle of Pretend — "Exhaust a friendly unit. If you do, for each
                          // different aspect it has, exhaust an enemy unit in the same arena."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit", "LAW_226#0");
            return;
};
