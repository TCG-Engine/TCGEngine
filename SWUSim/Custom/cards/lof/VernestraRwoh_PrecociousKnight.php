<?php
// LOF_195
// Cost 3 - Vernestra Rwoh - Precocious Knight - [Cunning,Heroism] - Power 3 - HP 4
// Text: When Played: You may use the Force (lose your Force token). If you do, ready this unit.

// LOF_195 Vernestra Rwoh — When Played: may use the Force → ready this unit.
$whenPlayedAbilities["LOF_195:0"] = function($player, $mzID) {
    $o = GetZoneObject($mzID);
    $uid = SWUObjUID($o, 0);
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_ready_this_unit?", "LOF_195#0|{$uid}");
};

$customDQHandlers["LOF_195#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
