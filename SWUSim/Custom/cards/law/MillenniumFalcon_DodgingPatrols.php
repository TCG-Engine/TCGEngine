<?php
// LAW_068
// Cost 3 - Millennium Falcon - Dodging Patrols - [Command,Cunning,Heroism] - Power 2 - HP 5
// Text: On Attack: You may give a space unit -2/-0 for this phase. You may give a ground unit +2/+0 for this phase.

// LAW_068 Millennium Falcon — On Attack: you may give a space unit -2/-0 for this phase; you may give
// a ground unit +2/+0 for this phase.
$onAttackAbilities["LAW_068:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $space = SWUAllUnits(null, SpaceArena);
    if (empty($space)) { MillenniumFalconDodgingPatrolsGround(intval($player)); return; }
    SWUQueueMayChooseTarget(intval($player), $space, "Give_a_space_unit_-2/-0?", "Choose_a_space_unit", "LAW_068#0");
};

$customDQHandlers["LAW_068#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($lastDecision, 2, 0, 'LAW_068D');
    }
    MillenniumFalconDodgingPatrolsGround(intval($player));
};

function MillenniumFalconDodgingPatrolsGround(int $player)
{
  global $playerID;
  $playerID = $player;
  $ground = SWUAllUnits(null, GroundArena);
  if (empty($ground))
    return;
  SWUQueueMayChooseTarget($player, $ground, "Give_a_ground_unit_+2/+0?", "Choose_a_ground_unit", "APPLY_PHASE_BUFF|2|0|LAW_068");
}
