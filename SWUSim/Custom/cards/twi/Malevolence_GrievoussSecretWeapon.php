<?php
// TWI_039
// Cost 9 - Malevolence - Grievous's Secret Weapon - [Vigilance,Villainy] - Power 7 - HP 7
// Text: Exploit 4 / Restore 2 / When Played: Give an enemy unit -4/-0 for this phase. It can't attack for this phase.

// TWI_039 Malevolence — "When Played: Give an enemy unit -4/-0 for this phase. It can't attack for
// this phase." (Restore 2 + Exploit 4 are keywords.)
$whenPlayedAbilities["TWI_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']),
        ZoneSearch('theirSpaceArena', ['Unit', 'Token Unit', 'Leader Unit'])
    );
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_enemy_unit_-4/-0_and_can't_attack_this_phase", "TWI_039#0");
};

$customDQHandlers["TWI_039#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUApplyPhaseDebuff($lastDecision, 4, 0, 'TWI_039');
    AddTurnEffect($lastDecision, 'CANT_ATTACK');
};
