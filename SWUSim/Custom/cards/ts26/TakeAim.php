<?php
// TS26_83
// Cost 3 - Take Aim - [Cunning]
// Text: This event costs 1 resource less to play for each friendly leader unit. / Attack with a unit. It gets +2/+0 and gains Saboteur for this attack. (When this unit attacks, ignore Sentinel and defeat the defender's Shields.)

// TS26_83 Take Aim — the chosen unit attacks with +2/+0 and Saboteur for this attack.
$customDQHandlers["TS26_83#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('SABOTEUR', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_83:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $ready[] = $mz;
        }
    }
    if (empty($ready)) return;
    SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+2/+0_and_Saboteur)", "TS26_83#0");
};
