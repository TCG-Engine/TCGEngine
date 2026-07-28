<?php
// TWI_082
// Cost 3 - MagnaGuard Wing Leader - [Command,Villainy] - Power 3 - HP 4
// Text: Action: Attack with a Droid unit. Then, attack with another Droid unit. Use this ability only once each round.

// TWI_082 MagnaGuard Wing Leader — "Action: Attack with a Droid unit. Then, attack with another Droid
// unit. Use this ability only once each round." (No exhaust/resource cost; once/round gate.)
$unitActionCostKind["TWI_082"] = 'none';

$unitAbilities["TWI_082"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $droids = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        $arr = GetZone($z);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (HasTrait($u->CardID ?? '', 'Droid')) $droids[] = "{$z}-{$i}";
        }
    }
    if (empty($droids)) { SWUAfterAction(intval($player)); return; }
    AddGlobalEffects(intval($player), 'SWU_TWI082_USED'); // once each round
    SWUQueueChooseTarget(intval($player), $droids, "Attack_with_a_Droid_unit", "TWI_082#0");
};

$customDQHandlers["TWI_082#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    SetSWUVar('SWU_CHAINED_ATTACK', "0,0,0," . intval($o->UniqueID ?? 0) . ",Droid"); // then another Droid, mandatory
    BeginSWUAttack(intval($player), $lastDecision);
};
