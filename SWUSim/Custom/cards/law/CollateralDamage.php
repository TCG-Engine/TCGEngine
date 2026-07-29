<?php
// LAW_208
// Cost 3 - Collateral Damage - [Aggression]
// Text: Deal 2 damage to a unit. Then, deal 2 damage to a base or another unit in the same arena.

// LAW_208 Collateral Damage — step 0: deal 2 to the first unit; then choose a base or another unit in
// the same arena for the second 2.
$customDQHandlers["LAW_208#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    $isSpace  = (strpos($lastDecision, 'Space') !== false);
    // "Then" (CR 8.29.1): the whole event must resolve before a first-hit When-Defeated reaction (Onyx's
    // heal, an opponent's disclose, …) may resolve — hold it until the second clause is done (flushed in #1).
    SWUBeginDeferWhenDefeated();
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    // Second target: a base, or another unit in the same arena (excluding the first).
    $zones = $isSpace ? ["mySpaceArena", "theirSpaceArena"] : ["myGroundArena", "theirGroundArena"];
    $targets = ['myBase-0', 'theirBase-0'];
    foreach ($zones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $u = GetZoneObject($mz);
            if (SWUObjGone($u)) continue;
            if (intval($u->UniqueID ?? 0) === $firstUID) continue;
            $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_base_or_another_unit_in_the_same_arena", "LAW_208#1");
};

$customDQHandlers["LAW_208#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        if (strpos($lastDecision, 'Base') !== false) {
            $tp = SWUMzOwner((string)$lastDecision, intval($player));   // Twin Suns: my/their/p{n} → owner seat
            SWUDealDamageToBase(2, $tp);
        } else {
            $u = GetZoneObject($lastDecision);
            if ($u !== null && empty($u->removed)) SWUDealDamageToUnit($lastDecision, 2, intval($player));
        }
    }
    // Event fully resolved — release the first clause's parked When-Defeated / bounty (CR 8.29.1 / 7.6.14.a),
    // now ordered active-player-first alongside any second-hit defeat trigger.
    SWUFlushDeferredWhenDefeated(intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_208:0"] = function($player, $mzID = '') {
// Collateral Damage — "Deal 2 damage to a unit. Then, deal 2 damage to a base
                          // or another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) {
                // No units in play → the first "deal 2 to a unit" clause has no target, but the second
                // clause still resolves: "a base or another unit in the same arena" reduces to just a base
                // (a base is not in an arena). Offer the base choice directly (deal 2 to a base).
                SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "LAW_208#1");
                return;
            }
            SWUQueueChooseTarget(intval($player), $units, "Deal_2_damage_to_a_unit", "LAW_208#0");
            return;
};
