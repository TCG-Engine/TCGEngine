<?php
// ASH_089
// Cost 2 - Perseverance - [Vigilance]
// Text: Heal 3 damage from a unit and give a Shield token to it.

// ASH_089 Perseverance (event) — heal 3 damage from a unit and give a Shield token to it.
$customDQHandlers["ASH_089#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    OnHealUnit(intval($player), $lastDecision, 3);
    DoGiveShieldToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_089:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Heal_3_and_Shield_a_unit", "ASH_089#0");
};
