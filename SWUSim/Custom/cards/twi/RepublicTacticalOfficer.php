<?php
// TWI_091
// Cost 2 - Republic Tactical Officer - [Command,Heroism] - Power 1 - HP 4
// Text: When Played: You may attack with a Republic unit. It gets +2/+0 for this attack.

// TWI_091 Republic Tactical Officer — "When Played: You may attack with a Republic unit. It gets +2/+0
// for this attack." (Mirrors LOF_111 but Republic + a one-shot attack bonus.)
$whenPlayedAbilities["TWI_091:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1 && TraitContains($u, 'Republic')) $ready[] = "{$zone}-{$i}";
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_a_Republic_unit_(+2/+0)?", "Choose_a_Republic_unit", "TWI_091#0");
};

$customDQHandlers["TWI_091#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $a = GetZoneObject($lastDecision);
    if (SWUObjGone($a)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);  // +2/+0 for this attack only (one-shot)
    BeginSWUAttack(intval($player), $lastDecision);
};
