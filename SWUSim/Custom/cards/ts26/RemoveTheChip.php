<?php
// TS26_69
// Cost 2 - Remove the Chip - [Aggression]
// Text: Deal 2 damage to a unit. If it's a Clone, ready it.

// TS26_69 Remove the Chip — deal 2 to the chosen unit; if it survives and is a Clone, ready it.
$customDQHandlers["TS26_69#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $o = GetZoneObject($lastDecision);   // survives → index unchanged; defeated → skip ready
    if ($o !== null && empty($o->removed) && TraitContains($o, 'Clone')) OnReadyCard(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_69:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = array_merge(
        ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
        ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
    );
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_2_damage_to_a_unit", "TS26_69#0");
};
