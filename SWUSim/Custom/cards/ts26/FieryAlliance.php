<?php
// TS26_25
// Cost 2 - Fiery Alliance - [Command,Aggression] - Upgrade Power 2 - Upgrade HP 2
// Text: When Played: You may deal 1 damage to another friendly unit and attack with it.

// TS26_25 Fiery Alliance (upgrade) — When Played: you may deal 1 damage to another friendly unit and
// attack with it. ($mzID = host; "another" = a friendly unit other than the host.)
$whenPlayedAbilities["TS26_25:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $hostUID = SWUObjUID($host);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1
                && intval($o->UniqueID ?? -2) !== $hostUID) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_1_to_another_friendly_unit_and_attack_with_it?", "Choose_a_friendly_unit", "TS26_25#0");
};

$customDQHandlers["TS26_25#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // SWU_TRIGGER_RESUME owns the close
    $o = GetZoneObject($lastDecision);
    $uid = SWUObjUID($o);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = ($uid >= 0) ? SWUFindMzByUID($uid) : null;
    if ($mz === null) return;   // the unit died to the 1 damage → can't attack
    BeginSWUAttack(intval($player), $mz);   // combat owns the after-action
};
