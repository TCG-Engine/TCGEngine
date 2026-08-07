<?php
// IC27_001
// Cost 7 - Darth Vader - No One to Stop Us - [Vigilance,Villainy] - Leader / Unit (Ground) 5/7
//   Traits: Force, Imperial, Sith
// Text: Action [1 resource, Exhaust, defeat a friendly unit]: Draw a card and heal 2 damage from your base.
// DeployText: On Attack: You may defeat another friendly unit. If you do, draw a card and heal 2 damage
//             from your base.
// Epic Action: If you control 7 or more resources, deploy this leader.
//
// ⚠ THE WHOLE CARD IS THE COST-vs-EFFECT DISTINCTION, and the two sides land on opposite sides of it
// (user ruling 2026-08-04) — structurally identical to SOR_006 Emperor Palpatine on BOTH sides:
//   * FRONT: the defeat is inside the bracketed COST, so it is a cost REQUIREMENT — with no friendly
//     unit the Action is unavailable (gated in SWULeaderActionAffordable beside SOR_006) and the
//     leader does not exhaust. There is no decline: paying is committing.
//   * DEPLOYED: the defeat is an EFFECT behind "you may … If you do", so the ability always fires, the
//     player may decline for free, and nothing is gated on a sacrifice being available.
// Epic deploy needs no wiring — the generic threshold IS the leader's printed cost (7).

// Front-side [1 resource] component: read by SWULeaderActionAffordable BEFORE the leader exhausts.
// ($leaderActionResourceCosts is initialized in LeaderAbilities.php, loaded BEFORE cards/_loader.php.)
$leaderActionResourceCosts["IC27_001"] = 1;

// Shared payoff for both sides: "draw a card and heal 2 damage from your base".
function Ic27001DrawAndHeal(int $player): void {
    DoDrawCard($player, 1);
    OnHealBase($player, $player, 2);   // clamped at 0 by OnHealBase
}

// ── FRONT: Action [1 resource, Exhaust, defeat a friendly unit] ──────────────
// SWULeaderAction exhausts the leader AND pays the [1 resource] (through the Credit/Droid alt-pay
// funnel) before this runs, so the closure only takes the sacrifice. Affordability already guaranteed
// a friendly unit exists.
$leaderAbilities["IC27_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = array_values(SWUAllUnits('my'));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Choose_a_friendly_unit_to_defeat_as_the_cost", "IC27_001#0");
};

$customDQHandlers["IC27_001#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    SWUDefeatUnit(intval($player), $lastDecision);
    Ic27001DrawAndHeal(intval($player));
    SWUAfterAction(intval($player));   // the leader Action owns its After Action
};

// ── DEPLOYED: On Attack — you MAY defeat another friendly unit ───────────────
// "another" excludes Vader himself; with no other friendly unit there is nothing to offer, so no
// prompt is raised and the attack simply resolves. Combat owns the After Action.
$onAttackAbilities["IC27_001:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $others = array_values(array_filter(SWUAllUnits('my'), fn($mz) => $mz !== $mzID));
    if (empty($others)) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 0,
        tooltip: "Defeat_another_friendly_unit_to_draw_and_heal_2?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "IC27_001#1|{$mzID}", 0);
};

$customDQHandlers["IC27_001#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;              // declining costs nothing
    global $playerID; $playerID = intval($player);
    $selfMz  = $parts[0] ?? '';
    $targets = array_values(array_filter(SWUAllUnits('my'), fn($mz) => $mz !== $selfMz));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_another_friendly_unit_to_defeat", "IC27_001#2", 0);
};

$customDQHandlers["IC27_001#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatUnit(intval($player), $lastDecision);
    Ic27001DrawAndHeal(intval($player));              // "If you do" — only after the defeat happens
};
