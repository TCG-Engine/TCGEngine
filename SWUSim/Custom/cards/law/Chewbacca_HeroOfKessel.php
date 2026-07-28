<?php
// LAW_013
// Cost 4 - Chewbacca - Hero of Kessel - [Aggression,Heroism] - Power 5 - HP 6
// Text: Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2 damage to a unit and create a Credit token. / Epic Action [4 resources]: Deploy this leader.
// DeployText: On Attack: You may defeat a friendly resource. If you do, deal 2 damage to a unit and create a Credit token.

$leaderActionResourceCosts["LAW_013"] = 1;

$leaderAbilities["LAW_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    $res = &GetResources($player);
    $resTargets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) { if (isset($res[$i]->removed) && $res[$i]->removed) continue; $resTargets[] = "myResources-{$idx}"; $idx++; }
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

$onAttackAbilities["LAW_013:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $res = &GetResources(intval($player));
    $resTargets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) { if (isset($res[$i]->removed) && $res[$i]->removed) continue; $resTargets[] = "myResources-{$idx}"; $idx++; }
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
