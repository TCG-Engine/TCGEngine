<?php
// LOF_092
// Cost 1 - Point Rain Reclaimer - [Command,Heroism] - Power 1 - HP 2
// Text: When Played: If you control a Jedi unit, you may give an Experience token to this unit.

// LOF_092 Point Rain Reclaimer — When Played: if you control a Jedi unit, may give an Experience to this unit.
$whenPlayedAbilities["LOF_092:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hasJedi = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        if (TraitContains($u, 'Jedi')) { $hasJedi = true; break; }
    }
    if (!$hasJedi) return;
    $o = GetZoneObject($mzID);
    $uid = SWUObjUID($o, 0);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Give_this_unit_an_Experience_token?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_092#0|{$uid}", 1);
};

$customDQHandlers["LOF_092#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) DoGiveExperienceToken(intval($player), $mz);
};
