<?php
// SOR_040
// Cost 9 - Avenger - Hunting Star Destroyer - [Vigilance,Villainy] - Power 8 - HP 8
// Text: When Played/On Attack: An opponent chooses a non-leader unit they control. Defeat that unit.

// SOR_040 Avenger — "When Played/On Attack: An opponent chooses a non-leader unit they control.
// Defeat that unit." Shared WhenPlayed + On Attack closure: queue the cross-player choose via the
// intermediate CUSTOM (nonLeader=1) so the opponent picks one of their own non-leader units.
$whenPlayedAbilities["SOR_040:0"] = $onAttackAbilities["SOR_040:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "OPP_DEFEAT_OWN_UNIT|1", 1);
};
