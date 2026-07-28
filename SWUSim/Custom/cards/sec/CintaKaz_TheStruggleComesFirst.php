<?php
// SEC_172
// Cost 6 - Cinta Kaz - The Struggle Comes First - [Aggression] - Power 5 - HP 5
// Text: When Played: You may attack with a unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_172 Cinta Kaz — When Played: you may attack with a unit.
$whenPlayedAbilities["SEC_172:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_a_unit?", "Choose_a_unit_to_attack_with", "SEC_172#0");
};

$customDQHandlers["SEC_172#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    BeginSWUAttack(intval($player), $lastDecision);
};
