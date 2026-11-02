<?php
// SHD_087
// Cost 4 - Crosshair - Following Orders - [Command,Villainy] - Power 2 - HP 6
// Text: Action [2 resources]: This unit gets +1/+0 for this phase. / Action [Exhaust]: This unit deals damage equal to his power to an enemy ground unit.

// SHD_087 Crosshair — TWO unit actions on one unit, each a named Action ability under its own id (the player picks
// one from the unit's menu; SWUUnitAction pays each one's cost from these registries):
//   SHD_087.0  "Action [2 resources]: This unit gets +1/+0 for this phase."  costKind 'none' + 2 resources
//   SHD_087.1  "Action [Exhaust]: This unit deals damage equal to his power to an enemy ground unit."  costKind exhaust
// (Until named Action abilities, one closure offered a Buff/Deal OPTIONCHOOSE of the affordable sub-actions.)
$unitActionCostKind["SHD_087.0"]      = 'none';
$unitActionResourceCosts["SHD_087.0"] = 2;
$unitAbilities["SHD_087.0"] = function($player, $mzID) { CrosshairFollowingOrdersBuff($player, $mzID); };

$unitActionCostKind["SHD_087.1"] = 'exhaust';
$unitAbilities["SHD_087.1"] = function($player, $mzID) { CrosshairFollowingOrdersDeal($player, $mzID); };

// A game SAVED with the old Buff/Deal chooser open still answers through this handler. The old closure paid nothing
// up front, so the answer pays the cost the dispatcher now pays: 2 resources for Buff, the exhaust for Deal.
$customDQHandlers["SHD_087#choice"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mzID = $parts[0] ?? '';
    if ($lastDecision === 'Buff') {
        SWUPayInlineAbilityCost(intval($player), 2);
        CrosshairFollowingOrdersBuff($player, $mzID);
    } elseif ($lastDecision === 'Deal') {
        $obj = GetZoneObject($mzID);
        if (!SWUObjGone($obj)) $obj->Status = 0;
        CrosshairFollowingOrdersDeal($player, $mzID);
    } else {
        SWUAfterAction(intval($player));
    }
};

// The dispatcher has already paid the 2 resources.
function CrosshairFollowingOrdersBuff($player, string $mzID): void
{
  global $playerID;
  $playerID = intval($player);
  SWUApplyPhaseBuff($mzID, 1, 0, 'SHD_087');          // +1/+0 for this phase
  SWUAfterAction(intval($player));
}

// The dispatcher has already exhausted Crosshair (the [Exhaust] cost).
function CrosshairFollowingOrdersDeal($player, string $mzID): void
{
  global $playerID;
  $playerID = intval($player);
  $obj = GetZoneObject($mzID);
  if (SWUObjGone($obj)) {
    SWUAfterAction(intval($player));
    return;
  }
  $pow = intval(ObjectCurrentPower($obj));
  $targets = array_values(array_filter(
    ZoneSearch('theirGroundArena', AnyUnitFilter),
    fn($m) => ($o = GetZoneObject($m)) !== null && empty($o->removed)
  ));
  if (empty($targets) || $pow <= 0) {
    SWUAfterAction(intval($player));
    return;
  }
  SWUQueueChooseTarget(intval($player), $targets, "Deal_{$pow}_damage_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|{$pow}|" . _SWUEncodeDamageSource($mzID));   // "This unit deals" — Crosshair is the source
  SWUQueueAfterAction(intval($player));
}
