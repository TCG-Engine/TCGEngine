<?php
// LAW_191
// Cost 3 - Arvel Skeen - Win and Walk Away - [Aggression] - Power 4 - HP 3
// Text: When Played/On Attack: You may defeat a Credit token (belonging to any player). If you do, deal 1 damage to a unit or base.

$customDQHandlers["LAW_191#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUDefeatCreditToken($lastDecision)) return;
    $targets = _SWUAllUnitsAndBases(intval($player));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_unit_or_base", "DEAL_TARGET|1");
};

// LAW_191 Arvel Skeen — When Played/On Attack: you may defeat a Credit token (any player's). If you do,
// deal 1 damage to a unit or base.
$law191 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $credits = array_merge(SWUUsableCreditTokenMzIDs(intval($player)), SWUEnemyCreditTokenMzIDs(intval($player)));
  if (empty($credits))
    return;
  SWUQueueMayChooseTarget(intval($player), $credits, "Defeat_a_Credit_token?", "Choose_a_Credit_token", "LAW_191#0");
};

$whenPlayedAbilities["LAW_191:0"] = $law191;

$onAttackAbilities["LAW_191:0"] = $law191;
