<?php
// TWI_156
// Cost 6 - Unlimited Power - [Aggression,Aggression]
// Text: Deal 4 damage to a unit, 3 damage to a second unit, 2 damage to a third unit, and 1 damage to a fourth unit. (All damage is dealt simultaneously.)

// TWI_156 Unlimited Power (event continuation) — parts = [thisAmount, remainingAmountsCSV, accumulated
// "uid:amt&uid:amt"]. Record this pick, then queue the next amount over the remaining units, or finalize
// with a simultaneous split-damage of all accumulated hits.
$customDQHandlers["TWI_156#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $thisAmt = intval($parts[0] ?? 0);
    $remaining = ($parts[1] ?? '') === '' ? [] : explode(',', $parts[1]);
    $acc = $parts[2] ?? '';
    // Record the just-chosen unit's UID at this amount.
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $chosen = GetZoneObject($lastDecision);
        if ($chosen !== null && empty($chosen->removed)) {
            $uid = intval($chosen->UniqueID ?? 0);
            $acc = ($acc === '' ? '' : $acc . '&') . "{$uid}:{$thisAmt}";
        }
    }
    // Parse already-picked UIDs for exclusion.
    $pickedUids = [];
    if ($acc !== '') {
        foreach (explode('&', $acc) as $pair) { $pu = explode(':', $pair); if (isset($pu[0])) $pickedUids[] = intval($pu[0]); }
    }
    // If more amounts remain, offer the next over the not-yet-picked units.
    if (!empty($remaining)) {
        $nextAmt = intval($remaining[0]);
        $rest = implode(',', array_slice($remaining, 1));
        $targets = [];
        foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !in_array(intval($o->UniqueID ?? 0), $pickedUids, true)) $targets[] = $mz;
            }
        }
        if (!empty($targets)) {
            SWUQueueChooseTarget(intval($player), $targets, "Deal_{$nextAmt}_damage_to_another_unit", "TWI_156#0|{$nextAmt}|{$rest}|{$acc}");
            return;
        }
    }
    // Finalize: build the "mzID:amount,..." assignment and apply simultaneously.
    if ($acc === '') return;
    $assign = [];
    foreach (explode('&', $acc) as $pair) {
        $pu = explode(':', $pair);
        if (count($pu) < 2) continue;
        $mz = SWUFindMzByUID(intval($pu[0]));
        if ($mz !== null) $assign[] = "{$mz}:" . intval($pu[1]);
    }
    if (!empty($assign)) SWUDealSplitDamage(intval($player), implode(',', $assign));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_156:0"] = function($player, $mzID = '') {
// Unlimited Power — "Deal 4 damage to a unit, 3 to a second, 2 to a third, and 1
                          // to a fourth. (All simultaneously.)" Chain 4 ordered picks (each excluding the
                          // already-chosen), accumulate uid:amount, then apply all at once via SWUDealSplitDamage.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "TWI_156#0|4|3,2,1|");
            return;
};
