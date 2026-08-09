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
            // "another friendly unit" — the only exclusion is the host. Readiness is NOT a condition:
            // an exhausted unit is a legal choice, you just do as much as you can (it takes the 1 damage
            // and the attack half simply doesn't happen). Filtering to ready units hid those targets.
            if ($o !== null && empty($o->removed)
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
    // "…and attack with it" — this card does NOT say "even if it's exhausted" (compare TS26_02 Anakin /
    // TS26_04 Padmé / TS26_07 Asajj, which do). BeginSWUAttack does not enforce readiness for an
    // effect-driven attack, so an exhausted choice would have swung anyway; the damage half already
    // happened, and that is as much as you can do.
    $atk = GetZoneObject($mz);
    if ($atk === null || intval($atk->Status ?? 0) !== 1) return;
    BeginSWUAttack(intval($player), $mz);   // combat owns the after-action
};
