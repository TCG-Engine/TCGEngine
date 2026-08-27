<?php
// TWI_085
// Cost 6 - Kalani - Analytical General - [Command,Villainy] - Power 5 - HP 7
// Text: On Attack: You may choose another unit. If you have the initiative, you may choose up to 2 other units instead. Give each chosen unit +2/+2 for this phase.

// TWI_085 Kalani — "On Attack: You may choose another unit. If you have the initiative, you may choose
// up to 2 other units instead. Give each chosen unit +2/+2 for this phase."
$onAttackAbilities["TWI_085:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $others = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -2) !== $selfUID) $others[] = $mz;
        }
    }
    if (empty($others)) return;
    $max = min(HasInitiative(intval($player)) ? 2 : 1, count($others));
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
        "0|{$max}|" . implode('&', $others), 1, tooltip: 'Give_each_chosen_other_unit_+2/+2_this_phase');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_085#0', 1, dontSkipOnPass: 1);
    // Combat owns the after-action.
};

$customDQHandlers["TWI_085#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, 2, 2, 'TWI_085');
    }
};
