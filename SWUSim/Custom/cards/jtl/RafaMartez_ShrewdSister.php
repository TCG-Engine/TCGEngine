<?php
// JTL_219
// Cost 3 - Rafa Martez - Shrewd Sister - [Cunning] - Power 3 - HP 3
// Text: When Played/On Attack: Deal 1 damage to a friendly unit and ready a resource.

$customDQHandlers["JTL_219#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        $obj = GetZoneObject($lastDecision);
        if ($obj !== null && empty($obj->removed)) SWUDealDamageToUnit($lastDecision, 1, intval($player));
    }
    RafaMartezShrewdSisterReadyResource(intval($player));
};

// ── JTL_219 Rafa Martez — When Played/On Attack: Deal 1 damage to a friendly unit and ready a resource.
$jtl219 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $friendly = array_values(array_merge(
    ZoneSearch('myGroundArena', AnyUnitFilter),
    ZoneSearch('mySpaceArena', AnyUnitFilter)
  ));
  if (empty($friendly)) {
    RafaMartezShrewdSisterReadyResource(intval($player));
    return;
  }
  SWUQueueChooseTarget(intval($player), $friendly, "Deal_1_to_a_friendly_unit_(then_ready_a_resource)", "JTL_219#0");
};

$whenPlayedAbilities["JTL_219:0"] = $jtl219;

$onAttackAbilities["JTL_219:0"] = $jtl219;

function RafaMartezShrewdSisterReadyResource(int $player): void
{
  $res = &GetResources($player);
  foreach ($res as &$r) {
    if (empty($r->removed) && intval($r->Status) !== 1) {
      $r->Status = 1;
      break;
    }
  }
  unset($r);
}
