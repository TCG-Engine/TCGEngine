<?php
// SEC_231
// Cost 2 - Implicate - [Cunning]
// Text: Choose a unit. For this phase, it gains Sentinel and: "When this unit is attacked: Create a Spy token."

// SEC_231 Implicate (event) — the chosen unit gains Sentinel + a granted "When this unit is attacked:
// create a Spy token" for this phase. One SEC_231 token does both: the registry row grants Sentinel
// (GRANT_KEYWORD), and CollectCombatStep1Triggers checks the same marker to create the Spy on defense.
$customDQHandlers["SEC_231#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('SEC_231', [], SWU_DUR_PHASE));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_231:0"] = function($player, $mzID = '') {
// Implicate — Choose a unit. For this phase it gains Sentinel and "When this
                          // unit is attacked: create a Spy token." (Any unit, friendly or enemy.)
            global $playerID; $playerID = intval($player);
            $units = array_values(SWUAllUnits());
            if (empty($units)) return;
            SWUQueueChooseTarget($player, $units, "Choose_a_unit_to_gain_Sentinel_and_Spy-on-defense", "SEC_231#0");
            return;
};
