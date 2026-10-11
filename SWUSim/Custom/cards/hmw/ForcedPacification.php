<?php
// HMW_253 Forced Pacification — Event, cost 2, [Villainy], Plan.
// "Defeat any number of friendly units. For each friendly unit defeated this way, exhaust 2 enemy units."
// "friendly" spans the team; "any number" includes zero. Only a defeat that HAPPENS counts ("this way").
// The friendly defeats are one simultaneous batch. The exhausts are a pick of up to 2 × defeated enemy
// units, distinct, capped at the number of enemy units; already-exhausted enemies are legal picks.

$whenPlayedAbilities["HMW_253:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $friendly = SWUFriendlyUnits();
    if (empty($friendly)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|" . count($friendly) . "|" . implode('&', $friendly), 1,
        tooltip: "Defeat_any_number_of_friendly_units");
    // ⚠ dontSkipOnPass: the MZMULTICHOOSE above has a LITERAL-ZERO lower bound ("any number" includes
    // zero), so the client shows a real Pass button and submits 'PASS' — which SKIPS a plain CUSTOM
    // instead of running it. Harmless for this handler, whose first line declines anyway, but the
    // invariant is enforced repo-wide (DevTools/tests/dontskiponpass_zero_min_test.php) precisely so
    // nobody has to re-derive "is it harmless here?" per card. See DefeatNone_PassToken_NothingHappens.
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_253#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["HMW_253#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uids = [];
    foreach (explode('&', (string)$lastDecision) as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) $uids[] = intval($o->UniqueID ?? 0);
    }
    $k = 0;
    SWUSimulDefeatBegin();
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && SWUDefeatUnit(intval($player), $mz)) $k++;
    }
    SWUSimulDefeatEnd();
    DecisionQueueController::CleanupRemovedCards();
    if ($k <= 0) return;
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    $n = min(2 * $k, count($enemies));
    if ($n >= count($enemies)) {
        foreach ($enemies as $emz) OnExhaustCard(intval($player), $emz, intval($player));
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "{$n}|{$n}|" . implode('&', $enemies), 1,
        tooltip: "Exhaust_{$n}_enemy_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_253#1|{$n}", 1);
};

$customDQHandlers["HMW_253#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $cap = intval($parts[0] ?? 0);
    $done = 0;
    foreach (array_unique(explode('&', (string)$lastDecision)) as $mz) {
        if ($done >= $cap) break;
        if (SWUObjGone(GetZoneObject($mz))) continue;
        OnExhaustCard(intval($player), $mz, intval($player));
        $done++;
    }
};
