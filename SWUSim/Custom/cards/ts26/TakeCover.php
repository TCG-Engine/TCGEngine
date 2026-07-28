<?php
// TS26_47
// Cost 3 - Take Cover - [Vigilance]
// Text: This event costs 1 resource less to play for each friendly leader unit. / Heal up to 3 damage from a unit and give a Shield token to it.

// TS26_47 Take Cover — heal up to 3 from the chosen unit (OnHealUnit clamps at its damage) and shield it.
$customDQHandlers["TS26_47#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    OnHealUnit(intval($player), $lastDecision, 3);
    DoGiveShieldToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_47:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Heal_up_to_3_and_Shield_a_unit", "TS26_47#0");
};
