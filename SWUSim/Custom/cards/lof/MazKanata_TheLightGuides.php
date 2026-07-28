<?php
// LOF_111
// Cost 3 - Maz Kanata - The Light Guides - [Command] - Power 3 - HP 4
// Text: When Played: You may attack with a Force unit. It gets +2/+0 for this attack.

// LOF_111 Maz Kanata — When Played: may attack with a Force unit. It gets +2/+0 for this attack.
$whenPlayedAbilities["LOF_111:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1 && TraitContains($u, 'Force')) $ready[] = "{$zone}-{$i}";
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_a_Force_unit_(+2/+0)?", "Choose_a_Force_unit", "LOF_111#0");
};

$customDQHandlers["LOF_111#0"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $a = GetZoneObject($lastDecision);
  if (SWUObjGone($a))
    return;
  SWUApplyPhaseBuff($lastDecision, 2, 0, 'LOF_111'); // +2/+0 for this attack
  BeginSWUAttack(intval($player), $lastDecision);
};
