<?php
// LOF_047
// Cost 3 - T-6 Shuttle 1974 - Stay Close - [Vigilance,Heroism] - Power 3 - HP 4
// Text: When this unit is attacked (before damage is dealt): You may give an Experience token to this unit.

$onDefenseAbilities["LOF_047:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? -1);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Give_an_Experience_token_to_this_unit?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "LOF_047#0|{$uid}", 1);
};

$customDQHandlers["LOF_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? -1));
    if ($mz === null || $mz === '') return;
    DoGiveExperienceToken(intval($player), $mz);
};
