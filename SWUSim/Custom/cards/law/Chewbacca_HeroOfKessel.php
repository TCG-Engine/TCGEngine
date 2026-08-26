<?php
// LAW_013
// Cost 4 - Chewbacca - Hero of Kessel - [Aggression,Heroism] - Power 5 - HP 6
// Text: Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2 damage to a unit and create a Credit token. / Epic Action [4 resources]: Deploy this leader.
// DeployText: On Attack: You may defeat a friendly resource. If you do, deal 2 damage to a unit and create a Credit token.

$leaderActionResourceCosts["LAW_013"] = 1;

// The "[1 resource]" component is paid centrally by SWULeaderAction (a Credit token may pay it, CR 3.13).
// This closure takes only the OTHER cost — "defeat a friendly resource" — for which a Credit token is NOT
// a legal choice: Credits sit in the resource zone but are explicitly not resources (CR 3.13).
$leaderAbilities["LAW_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $resTargets = ChewbaccaHeroofKesselResourceTargets($player);
    if (empty($resTargets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $resTargets, "Defeat_a_friendly_resource_(cost)", "LAW_013#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_013#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDefeatResource(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    ChewbaccaHeroofKesselPayoff(intval($player));
};

// mzIDs of the player's real resources — the legal choices for "defeat a friendly resource". Credit
// tokens live in the same zone but are NOT resources (CR 3.13), so they are never offered. The index is
// the LIVE zone position (Credits included) because that is what GetZoneObject resolves.
function ChewbaccaHeroofKesselResourceTargets(int $player): array {
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $out = SWUFriendlyResourceMzIDs(intval($player), fn($o) => !SWUIsCreditToken($o->CardID ?? ''));
    return $out;
}

$onAttackAbilities["LAW_013:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $resTargets = ChewbaccaHeroofKesselResourceTargets(intval($player));
    if (empty($resTargets)) return;
    SWUQueueMayChooseTarget(intval($player), $resTargets, "Defeat_a_friendly_resource_to_deal_2_and_create_a_Credit?", "Choose_a_resource", "LAW_013#1");
};

$customDQHandlers["LAW_013#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatResource(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    ChewbaccaHeroofKesselPayoff(intval($player));
};

// ── LAW_013 Chewbacca ─────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust, defeat a friendly resource]: deal 2 to a unit and create a Credit.
// Deployed On Attack: you MAY defeat a friendly resource → deal 2 to a unit and create a Credit.
function ChewbaccaHeroofKesselPayoff(int $player): void {   // after the resource is defeated: credit + deal 2 to a unit
    global $playerID; $playerID = $player;
    SWUCreateCreditToken($player, 1);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $units[] = $mz; }
    if (empty($units)) return;
    SWUQueueChooseTarget($player, $units, "Deal_2_damage_to_a_unit", "DEAL_UNIT_DAMAGE|2");
}
