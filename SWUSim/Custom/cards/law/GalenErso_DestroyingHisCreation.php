<?php
// LAW_233
// Cost 3 - Galen Erso - Destroying His Creation - [Cunning] - Power 0 - HP 5
// Text: When Played: You may have an opponent take control of this unit. / Enemy units gain Raid 1 and Saboteur.

// LAW_233 Galen Erso — When Played: you may have an opponent take control of this unit. (Passive
// "Enemy units gain Raid 1 and Saboteur" is in the conditional keyword functions.)
$whenPlayedAbilities["LAW_233:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Have_an_opponent_take_control_of_this_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_233#0|{$uid}", 1);
};

$customDQHandlers["LAW_233#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUTakeControlOfUnit(OtherPlayer(intval($player)), $mz);
};
