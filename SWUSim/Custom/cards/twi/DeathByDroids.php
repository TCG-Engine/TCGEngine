<?php
// TWI_076
// Cost 5 - Death by Droids - [Vigilance]
// Text: Defeat a unit that costs 3 or less. Create 2 Battle Droid tokens.

// TWI_076 Death by Droids continuation — defeat the chosen ≤3-cost unit, then create 2 Battle Droids.
// (The token creation always happens; the OnPlayEvent case handles the no-target branch directly.)
$customDQHandlers["TWI_076#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUDefeatUnit(intval($player), $lastDecision);
    }
    SWUCreateUnitTokens(intval($player), 'TWI_T01', 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_076:0"] = function($player, $mzID = '') {
// Death by Droids — "Defeat a unit that costs 3 or less. Create 2 Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
            // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
            // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
            // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
            foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) { SWUCreateUnitTokens(intval($player), 'TWI_T01', 2); return; }
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_that_costs_3_or_less", "TWI_076#0");
            return;
};
