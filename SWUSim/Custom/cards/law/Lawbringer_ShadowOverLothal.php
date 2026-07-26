<?php
// LAW_101
// Cost 8 - Lawbringer - Shadow Over Lothal - [Vigilance,Villainy] - Power 7 - HP 7
// Text: When Played/On Attack: Choose an aspect. Give each enemy unit with that aspect -2/-2 for this phase.

$customDQHandlers["LAW_101#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (strpos((string)(CardAspect($o->CardID ?? '') ?? ''), $lastDecision) !== false) {
                SWUApplyPhaseDebuff($mz, 2, 2, 'LAW_101');
            }
        }
    }
};

// LAW_101 Lawbringer — When Played/On Attack: choose an aspect; give each enemy unit with that aspect
// -2/-2 for this phase.
$law101 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Vigilance&Command&Aggression&Cunning&Heroism&Villainy", 1, "Choose_an_aspect");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_101#0|" . OtherPlayer(intval($player)), 1);
};

$whenPlayedAbilities["LAW_101:0"] = $law101;

$onAttackAbilities["LAW_101:0"] = $law101;
