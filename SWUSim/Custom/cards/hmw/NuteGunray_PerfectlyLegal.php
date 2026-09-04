<?php
// HMW_105
// Cost 2 - Nute Gunray - Perfectly Legal - [Command,Villainy] - Power 2 - HP 2 - Separatist, Official
// Text: When Played: Each friendly unit (including this one) deals 1 damage to a different enemy unit.

// HMW_105 Nute Gunray — When Played: each friendly unit, Nute included, deals 1 damage to a DIFFERENT
// enemy unit.
//
// Shape: one MZMULTICHOOSE of exactly k distinct enemy units, then one point of damage per pick. That is
// the JTL_174 Hotshot Maneuver seam — the only released card carrying this same "a different enemy unit"
// clause — rather than k chained single-target prompts.
//
//   k = min(#friendly dealers, #enemy units). "A DIFFERENT enemy unit" is what caps it: three dealers
//   against one enemy deal ONE damage, not three, and two dealers against four enemies damage exactly
//   two. Both directions are pinned (MoreFriendliesThanEnemies / FewerFriendliesThanEnemies).
//
//   "FRIENDLY" spans the TEAM in Team Suns — SWUFriendlyUnitObjects, not GetUnitsInPlay ("you control").
//   "(INCLUDING THIS ONE)" means no self-exclusion: Nute alone is still one dealer.
//
// ⚠ EACH FRIENDLY UNIT IS THE DAMAGE SOURCE, not Nute and not "the ability" (CR 9.12 — a unit's ability
// deals its damage). Each point is dealt with its dealer's mzID as $sourceMzID so source-aware readers
// (damage-increase and source-conditional prevention) see the right unit. The dealer→target PAIRING is
// assigned positionally rather than prompted for: every dealer deals exactly 1 and every target takes
// exactly one instance, so the pairing is unobservable except to a source-conditional effect, and k
// extra prompts to choose it would be a real UX cost for a case that may not exist. FLAGGED as a
// preview-set assumption (HMW has no card rulings yet).
//
// ⚠ Damage is applied SEQUENTIALLY, matching JTL_174 and every other multi-target damage card in the
// engine — there is deliberately no SWUSimulDefeatBegin/End window here. If these defeats should be
// simultaneous (the Rancor Keeper ruling's shape), that is a FAMILY decision, not a Nute one, and the
// natural co-victim fixture (ASH_127 The Twins) still reads a live in-play count rather than
// _SWUSimulObserverCount — so a window added here could not be pinned by a discriminating section.
$whenPlayedAbilities["HMW_105:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Snapshot the dealers NOW: "each friendly unit" is the set in play when the ability resolves.
    $dealers = [];
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (SWUObjGone($u)) continue;
        $uid = intval($u->UniqueID ?? 0);
        if ($uid > 0) $dealers[] = $uid;
    }
    $enemies = array_values(array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)));
    $k = min(count($dealers), count($enemies));
    if ($k <= 0) return;                       // no dealers or no enemy units → resolves to nothing
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "{$k}|{$k}|" . implode('&', $enemies), 1, tooltip: "Deal_1_to_{$k}_different_enemy_units");
    // The dealer UIDs and the offered COUNT both ride the continuation's own Param: they are read in the
    // request AFTER the pick, where an in-memory global would be empty, and the harness hands an answer
    // straight to the handler without enforcing the decision's cap — so k has to be re-clamped below or
    // an over-answer would deal more damage than the board allows.
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "HMW_105#0|{$k}|" . implode(',', $dealers), 1);
};

$customDQHandlers["HMW_105#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $k       = max(0, intval($parts[0] ?? 0));
    $dealers = array_values(array_filter(array_map('intval', explode(',', (string)($parts[1] ?? '')))));
    if ($k <= 0 || empty($dealers)) return;
    if (SWUDecisionDeclined($lastDecision) || (string)$lastDecision === '') return;
    // Resolve the picks to UniqueIDs before dealing anything: a point of damage can defeat its target and
    // compact the arena underneath the remaining picks, so positional mzIDs go stale mid-loop.
    $targets = [];
    foreach (explode('&', (string)$lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        $uid = intval($o->UniqueID ?? 0);
        if ($uid > 0 && !in_array($uid, $targets, true)) $targets[] = $uid;   // "a DIFFERENT enemy unit"
    }
    $targets = array_slice($targets, 0, $k);                                  // re-clamp to the offer
    foreach ($targets as $i => $tuid) {
        $tmz = SWUFindMzByUID($tuid);
        if ($tmz === null) continue;                                          // already gone
        $smz = isset($dealers[$i]) ? SWUFindMzByUID($dealers[$i]) : null;
        SWUDealDamageToUnit($tmz, 1, intval($player), $smz);
    }
};
