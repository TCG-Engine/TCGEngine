<?php
// ASH_151
// Cost 6 - Operation Cinder - [Aggression,Villainy]
// Text: Deal 5 damage to your base. Then, deal 5 damage to each unit.

$whenPlayedAbilities["ASH_151:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // Dealer stated EXPLICITLY (3rd arg): this is SELF-damage, so the funnel's fallback inference
    // ("the damager is the other player") is exactly wrong here, and a "when YOU deal damage"
    // observer would credit the opponent — HMW_011 Darth Sidious reads this.
    SWUDealDamageToBase(5, intval($player), intval($player));   // 5 to your own base
    // Snapshot every unit's UID, then deal 5 to each (defeats resolve as we go).
    $uids = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
    // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
    // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
    foreach (["teamGroundArena", "teamSpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 5, intval($player));
    }
};
