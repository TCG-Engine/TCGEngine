<?php
// IBH_062
// Cost 2 - Imperial Deck Officer - [Vigilance] - Power 1 - HP 4
// Text: Action [Exhaust]: Heal 2 damage from a Villainy unit.

// IBH_062 / IBH_100 Imperial Deck Officer — Action [Exhaust]: heal 2 damage from a Villainy unit.
$unitAbilities["IBH_062"] =
$unitAbilities["IBH_100"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && strpos((string)CardAspect($o->CardID ?? ''), 'Villainy') !== false) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Heal_2_from_a_Villainy_unit", "HEAL_TARGET|2");
    SWUQueueAfterAction($player);
};
