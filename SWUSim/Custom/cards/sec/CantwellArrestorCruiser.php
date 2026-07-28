<?php
// SEC_037
// Cost 7 - Cantwell Arrestor Cruiser - [Vigilance,Villainy] - Power 6 - HP 7
// Text: When Played: You may disclose VigilanceVigilanceVillainy (reveal cards from your hand with these aspect icons among them). If you do, exhaust an enemy unit. That unit can't ready while this unit is in play.

// SEC_037 Cantwell Arrestor Cruiser — When Played: you may disclose VigilanceVigilanceVillainy →
// exhaust an enemy unit; that unit can't ready while THIS unit is in play (a per-instance lock keyed
// by the chosen target's UID + SEC_037's UID; see _SWUReadyLockedWhileSourceInPlay in GameLogic.php).
$whenPlayedAbilities["SEC_037:0"] = function($player, $mzID) {
    $self   = GetZoneObject($mzID);
    $srcUID = SWUObjUID($self, 0);
    SWUQueueDisclose(intval($player), ['Vigilance', 'Vigilance', 'Villainy'], "SEC_037#0|{$srcUID}",
        "Disclose_VigilanceVigilanceVillainy_to_exhaust_an_enemy_unit");
};

$customDQHandlers["SEC_037#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $srcUID = intval($parts[0] ?? 0);
  $enemy = SWUAllUnits('their');
  if (empty($enemy))
    return;
  SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "SEC_037#1|{$srcUID}");
};

$customDQHandlers["SEC_037#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $srcUID = intval($parts[0] ?? 0);
  if (SWUDecisionDeclined($lastDecision))
    return;
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o))
    return;
  OnExhaustCard(intval($player), $lastDecision);
  $tgtUID = intval($o->UniqueID ?? 0);
  $tgtCtrl = intval($o->Controller ?? GetOpponent(intval($player)));
  if ($tgtUID > 0 && $srcUID > 0)
    AddGlobalEffects($tgtCtrl, 'SWU_CR37_' . $tgtUID . '_' . $srcUID);
};
