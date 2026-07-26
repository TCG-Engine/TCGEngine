<?php
// SEC_253
// Cost 4 - Covert Operative - [Heroism] - Power 2 - HP 4
// Text: When Played: This unit captures an enemy non-leader unit that costs 2 or less. (Put the captured card facedown under this unit until this unit leaves play.)

// SEC_253 Covert Operative — When Played: this unit captures an enemy non-leader unit that costs 2 or less.
$whenPlayedAbilities["SEC_253:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 2) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Capture_an_enemy_unit_costing_2_or_less", "SEC_253#0|{$selfUID}");
};

$customDQHandlers["SEC_253#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};
