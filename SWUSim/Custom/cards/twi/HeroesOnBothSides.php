<?php
// TWI_249
// Cost 2 - Heroes on Both Sides - [Heroism]
// Text: Choose up to 1 Republic unit and up to 1 Separatist unit. Give each chosen unit +2/+2 and Saboteur for this phase. (When either of those units attacks, ignore Sentinel and defeat the defender's Shields.)

// TWI_249 Heroes on Both Sides — Republic pick (may) → buff, then offer the Separatist pick.
$customDQHandlers["TWI_249#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) {
      SWUApplyPhaseBuff($lastDecision, 2, 2, 'TWI_249');
      AddTurnEffect($lastDecision, 'SABOTEUR');
    }
  }
  $sep = ($parts[0] ?? '') !== '' ? explode('&', $parts[0]) : [];
  $sep = array_values(array_filter($sep, function ($mz) {
    $o = GetZoneObject($mz);
    return $o !== null && empty($o->removed); }));
  if (!empty($sep))
    SWUQueueMayChooseTarget(intval($player), $sep, "Give_a_Separatist_unit_+2/+2_and_Saboteur?", "Choose_a_Separatist_unit", "TWI_249#1");
};

$customDQHandlers["TWI_249#1"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o))
    return;
  SWUApplyPhaseBuff($lastDecision, 2, 2, 'TWI_249');
  AddTurnEffect($lastDecision, 'SABOTEUR');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_249:0"] = function($player, $mzID = '') {
// Heroes on Both Sides — "Choose up to 1 Republic unit and up to 1 Separatist
                          // unit. Give each chosen unit +2/+2 and Saboteur for this phase."
            global $playerID; $playerID = intval($player);
            $rep = []; $sep = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    if (HasTrait($o->CardID ?? '', 'Republic')) $rep[] = $mz;
                    if (HasTrait($o->CardID ?? '', 'Separatist')) $sep[] = $mz;
                }
            }
            if (!empty($rep)) SWUQueueMayChooseTarget(intval($player), $rep, "Give_a_Republic_unit_+2/+2_and_Saboteur?", "Choose_a_Republic_unit", "TWI_249#0|" . implode('&', $sep));
            elseif (!empty($sep)) SWUQueueMayChooseTarget(intval($player), $sep, "Give_a_Separatist_unit_+2/+2_and_Saboteur?", "Choose_a_Separatist_unit", "TWI_249#1");
            return;
};
