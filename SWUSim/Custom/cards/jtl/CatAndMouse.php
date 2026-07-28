<?php
// JTL_195
// Cost 3 - Cat and Mouse - [Cunning,Villainy]
// Text: Exhaust an enemy unit. If you do, ready a friendly unit in the same arena with power equal to or less than that enemy unit.

// ── JTL_195 Cat and Mouse (event continuation) — exhaust the chosen enemy; ready a friendly in the
// same arena with power <= that enemy's power. ───────────────────────────────────────────────────────
$customDQHandlers["JTL_195#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $eo = GetZoneObject($lastDecision);
    if (SWUObjGone($eo)) return;
    $epower = ObjectCurrentPower($eo);
    // "Exhaust an enemy unit. IF YOU DO, ready a friendly unit …" — exhausting an ALREADY-exhausted unit
    // does nothing, so the "if you do" clause fails and no friendly unit readies.
    $wasReady = intval($eo->Status ?? 0) === 1;
    OnExhaustCard(intval($player), $lastDecision);
    if (!$wasReady) return;
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'mySpaceArena' : 'myGroundArena';
    $friendly = [];
    foreach (ZoneSearch($arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) <= $epower) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly,
        "Ready_a_friendly_unit_with_power_<=_that_enemy", "READY_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_195:0"] = function($player, $mzID = '') {
// Cat and Mouse — exhaust an enemy unit; if you do, ready a friendly unit in the
                          // same arena with power <= that enemy unit's power (continuation JTL_195).
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemies)) return;
            SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "JTL_195#0");
            return;
};
