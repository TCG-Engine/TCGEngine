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
    SWUCreateCreditToken(OtherPlayer(intval($player)), 1);   // "an opponent creates a Credit token"
};

$leaderAbilities["LAW_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) {
        // "An opponent creates a Credit token" is a separate, UNCONDITIONAL sentence — it still happens even
        // when there is no unit to receive the Experience (unlike the deployed On-Attack "if you do" side).
        SWUCreateCreditToken(OtherPlayer($player), 1);
        SWUAfterAction($player); return;
    }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_(an_opponent_creates_a_Credit)", "LAW_006#0");
    SWUQueueAfterAction($player);
};

$onAttackAbilities["LAW_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit_(opponent_creates_a_Credit)?", "Choose_a_unit", "LAW_006#0");
};
