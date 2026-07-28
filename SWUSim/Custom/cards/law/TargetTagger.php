<?php
// LAW_157
// Cost 3 - Target Tagger - [Command] - Power 3 - HP 3
// Text: When Played: You may attack with a unit. If it's a Bounty Hunter, it gets +2/+0 for this attack.

// LAW_157 Target Tagger — When Played: you may attack with a unit. If it's a Bounty Hunter, it gets
// +2/+0 for this attack.
$whenPlayedAbilities["LAW_157:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            $ready[] = "{$zone}-{$i}";
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_a_unit?", "Choose_a_unit_to_attack_with", "LAW_157#0");
};

$customDQHandlers["LAW_157#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (HasTrait($o->CardID ?? '', 'Bounty Hunter')) SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};
