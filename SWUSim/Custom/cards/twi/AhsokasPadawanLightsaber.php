<?php
// TWI_248
// Cost 1 - Ahsoka's Padawan Lightsaber - [Heroism] - Upgrade Power 2 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / When Played: If attached unit is Ahsoka Tano, you may attack with a unit.

// TWI_248 Ahsoka's Padawan Lightsaber — "When Played: If attached unit is Ahsoka Tano, you may attack
// with a unit." (Upgrade +2/+0, non-Vehicle attach; $mzID = the host.)
$whenPlayedAbilities["TWI_248:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host) || CardTitle($host->CardID ?? '') !== 'Ahsoka Tano') return;
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
    SWUQueueMayChooseTarget(intval($player), $ready, "Attack_with_a_unit?", "Choose_a_unit_to_attack_with", "TWI_248#0");
};

$customDQHandlers["TWI_248#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    BeginSWUAttack(intval($player), $lastDecision);
};
