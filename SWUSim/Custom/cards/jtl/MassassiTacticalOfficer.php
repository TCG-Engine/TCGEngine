<?php
// JTL_146
// Cost 1 - Massassi Tactical Officer - [Aggression,Heroism] - Power 0 - HP 4
// Text: Action [Exhaust]: Attack with a Fighter unit. It gets +2/+0 for this attack.

// JTL_146 Massassi Tactical Officer — Action [Exhaust]: Attack with a Fighter unit (+2/+0 this attack).
$unitAbilities["JTL_146"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $fighters = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (HasTrait($u->CardID, 'Fighter')) $fighters[] = "{$zone}-{$i}";
        }
    }
    if (empty($fighters)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $fighters, "Choose_a_Fighter_to_attack_with_(+2/+0)", "JTL_146#0");
};

$customDQHandlers["JTL_146#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') { SWUAfterAction($player); return; }
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) { SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);   // combat owns SWUAfterAction
};
