<?php
// TS26_84
// Cost 4 - Fearless Attack - [Heroism]
// Text: Attack with a unit. It gets +1/+0 for this attack for each unit controlled by the defending player.

// TS26_84 Fearless Attack — the chosen unit attacks with +1/+0 per unit the defending player controls
// (2-player: the opponent's total units, counted at declaration).
$customDQHandlers["TS26_84#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $n = 0;
    foreach (GetUnitsInPlay(OtherPlayer(intval($player))) as $u) { if (empty($u->removed)) $n++; }
    if ($n > 0) SWUAddAttackPowerBonus($lastDecision, $n);
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_84:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $ready[] = $mz;
        }
    }
    if (empty($ready)) return;
    SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+1/+0_per_enemy_unit)", "TS26_84#0");
};
