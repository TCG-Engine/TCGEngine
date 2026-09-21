<?php
// SHD_201
// Cost 4 - Principled Outlaw - [Cunning,Heroism] - Power 4 - HP 4
// Text: On Attack: You may exhaust a ground unit. / Smuggle [6 resources Cunning Heroism] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_201 Principled Outlaw ────────────────────────────────────────────────
// On Attack: You may exhaust a ground unit. Only READY units are offered (the engine's
// exhaust-only-ready convention, cf. SEC_069) — auto-passes when none.
$onAttackAbilities["SHD_201:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_a_ground_unit?", "Exhaust_a_ground_unit", "EXHAUST_UNIT");
};
