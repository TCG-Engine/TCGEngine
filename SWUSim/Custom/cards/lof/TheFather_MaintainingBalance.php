<?php
// LOF_260
// Cost 8 - The Father - Maintaining Balance - Power 5 - HP 10
// Text: When you use the Force: You may deal 1 damage to this unit. If you do, the Force is with you.

// LOF_260 The Father use-Force reaction — may deal 1 to itself; if so, the Force is with you.
$customDQHandlers["LOF_260#0"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  global $playerID;
  $playerID = intval($player);
  $mz = SWUFindMzByUID(intval($parts[0] ?? -1));
  if ($mz === null || $mz === '')
    return;
  $o = GetZoneObject($mz);
  if (SWUObjGone($o))
    return;
  SWUDealDamageToUnit($mz, 1, intval($player));
  TheForceIsWithYou(intval($player));
};
