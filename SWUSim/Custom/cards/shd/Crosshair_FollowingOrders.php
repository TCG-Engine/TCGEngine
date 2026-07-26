<?php
// SHD_087
// Cost 4 - Crosshair - Following Orders - [Command,Villainy] - Power 2 - HP 6
// Text: Action [2 resources]: This unit gets +1/+0 for this phase. / Action [Exhaust]: This unit deals damage equal to his power to an enemy ground unit.

// SHD_087 Crosshair — TWO unit actions on one unit: "Action [2 resources]: +1/+0 this phase" and
// "Action [Exhaust]: deal damage equal to his power to an enemy ground unit." SWUUnitAction resolves ONE
// provider per unit, so this closure presents a menu of the affordable sub-actions (each pays its own
// distinct cost — costKind 'none', no auto-cost). Availability (either sub-action affordable) is gated in
// SWUUnitActionAffordable.
$unitActionCostKind["SHD_087"] = 'none';

$unitAbilities["SHD_087"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if (SWUObjGone($obj)) { SWUAfterAction($player); return; }
    $canBuff = SWUResourceCount(intval($player), readyOnly: true) >= 2;
    $canDeal = intval($obj->Status ?? 0) === 1;         // exhaust cost → must be ready
    if (!$canBuff && !$canDeal) { SWUAfterAction($player); return; }
    if ($canBuff && !$canDeal) { CrosshairFollowingOrdersBuff($player, $mzID); return; }
    if ($canDeal && !$canBuff) { CrosshairFollowingOrdersDeal($player, $mzID); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Buff&Deal", 1, tooltip: "Crosshair:_gain_+1/+0_or_deal_power_to_an_enemy?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_087#choice|{$mzID}", 1);
};

$customDQHandlers["SHD_087#choice"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0] ?? '';
    if ($lastDecision === 'Buff')      CrosshairFollowingOrdersBuff($player, $mzID);
    elseif ($lastDecision === 'Deal')  CrosshairFollowingOrdersDeal($player, $mzID);
    else                               SWUAfterAction(intval($player));
};

function CrosshairFollowingOrdersBuff($player, string $mzID): void
{
  global $playerID;
  $playerID = intval($player);
  SWUExhaustResources(intval($player), 2);            // cost: 2 resources
  SWUApplyPhaseBuff($mzID, 1, 0, 'SHD_087');          // +1/+0 for this phase
  SWUAfterAction(intval($player));
}

function CrosshairFollowingOrdersDeal($player, string $mzID): void
{
  global $playerID;
  $playerID = intval($player);
  $obj = GetZoneObject($mzID);
  if (SWUObjGone($obj)) {
    SWUAfterAction(intval($player));
    return;
  }
  $obj->Status = 0;                                   // cost: exhaust this unit
  $pow = intval(ObjectCurrentPower($obj));
  $targets = array_values(array_filter(
    ZoneSearch('theirGroundArena', AnyUnitFilter),
    fn($m) => ($o = GetZoneObject($m)) !== null && empty($o->removed)
  ));
  if (empty($targets) || $pow <= 0) {
    SWUAfterAction(intval($player));
    return;
  }
  SWUQueueChooseTarget(intval($player), $targets, "Deal_{$pow}_damage_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|{$pow}");
  SWUQueueAfterAction(intval($player));
}
