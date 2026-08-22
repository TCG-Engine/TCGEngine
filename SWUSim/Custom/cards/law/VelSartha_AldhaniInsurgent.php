<?php
// LAW_006
// Cost 6 - Vel Sartha - Aldhani Insurgent - [Vigilance,Heroism] - Power 4 - HP 7
// Text: Action [Exhaust]: Give an Experience token to a unit. An opponent creates a Credit token.
// DeployText: On Attack: You may give an Experience token to a unit. If you do, an opponent creates a Credit token.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── LAW_006 Vel Sartha ────────────────────────────────────────────────────────
// Front Action [Exhaust]: give an Experience token to a unit; an opponent creates a Credit token.
// Deployed On Attack: MAY give an Experience token; if you do, an opponent creates a Credit token.
$customDQHandlers["LAW_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DoGiveExperienceToken(intval($player), $lastDecision);
    // The chosen opponent rides the Param; never re-derive it here.
    $opp = intval($parts[0] ?? 0);
    if ($opp > 0 && $opp !== intval($player)) SWUCreateCreditToken($opp, 1);   // "an opponent creates a Credit token"
};

$leaderAbilities["LAW_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    // "AN opponent creates a Credit token" — the caster picks WHICH. Queued FIRST, ahead of
    // SWUQueueAfterAction, so the whole chain resolves inside the action; invisible PASSPARAMETER at one
    // eligible opponent, so Premier is byte-identical (I1).
    // ⚠ NO $eligible filter: creating a Credit token can never fail for any live opponent.
    // ⚠ Picking FIRST is deliberate — the front has TWO sites that hand out the Credit (the normal path
    // and the "no unit to receive the Experience" path), and both must name the SAME seat. Choosing once,
    // up front, is what stops them drifting apart.
    SWUQueueChooseOpponent($player, 'LAW_006#2', "Choose_an_opponent_to_create_a_Credit_token");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_006#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) {
        // "An opponent creates a Credit token" is a separate, UNCONDITIONAL sentence — it still happens even
        // when there is no unit to receive the Experience (unlike the deployed On-Attack "if you do" side).
        SWUCreateCreditToken($opp, 1);
        return;
    }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_(an_opponent_creates_a_Credit)", "LAW_006#0|" . $opp);
};

$onAttackAbilities["LAW_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) return;
    // Deployed side is "…IF YOU DO, an opponent creates a Credit token", so the opponent pick is queued
    // AHEAD of the may-choose and simply goes unused when the player declines — LAW_006#0 returns early
    // on a declined target and never spends the Credit.
    SWUQueueChooseOpponent(intval($player), 'LAW_006#3', "Choose_an_opponent_to_create_a_Credit_token");
};

$customDQHandlers["LAW_006#3"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit_(opponent_creates_a_Credit)?", "Choose_a_unit", "LAW_006#0|" . $opp);
};
