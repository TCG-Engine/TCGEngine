<?php
// LOF_043
// Cost 5 - The Tragedy of Plagueis - [Vigilance,Villainy]
// Text: Choose a friendly unit. For this phase, it can't be defeated by having no remaining HP. / An opponent chooses a unit they control. Defeat that unit.

// LOF_043 The Tragedy of Plagueis — the chosen friendly unit can't be defeated by no remaining HP this
// phase (NO_HP_DEFEAT marker). (The opponent's "defeat a unit they control" half is OPP_DEFEAT_OWN_UNIT.)
$customDQHandlers["LOF_043#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'NO_HP_DEFEAT');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_043:0"] = function($player, $mzID = '') {
// The Tragedy of Plagueis — "Choose a friendly unit. For this phase, it can't be
                          // defeated by having no remaining HP. An opponent chooses a unit they control.
                          // Defeat that unit."
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (!empty($mine)) {
                SWUQueueChooseTarget(intval($player), $mine, "Choose_a_friendly_unit_(can't_be_defeated_by_no_HP_this_phase)", "LOF_043#0");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "OPP_DEFEAT_OWN_UNIT|0", 1);
            return;
};
