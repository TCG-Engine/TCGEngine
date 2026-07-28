<?php
// LAW_012
// Cost 4 - Sebulba - Especially Dangerous Dug - [Aggression,Villainy] - Power 2 - HP 5
// Text: Action [Exhaust, discard a card from your deck]: A friendly unit gains Raid 1 for this phase.
// DeployText: Raid 1 / On Attack: Discard a card from your deck.
// Epic Action: If you control 4 or more resources, deploy this leader.

// ── LAW_012 Sebulba ───────────────────────────────────────────────────────────
// Front Action [Exhaust, discard a card from your deck]: a friendly unit gains Raid 1 for this phase.
// Deployed: Raid 1 (auto) + On Attack: discard a card from your deck.
$leaderAbilities["LAW_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    SWUMillTopCard($player);   // pay the [discard a card from your deck] cost (deck non-empty per the gate)
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $friendly[] = $mz; }
    if (empty($friendly)) { SWUAfterAction($player); return; }   // costs paid; no legal target → no Raid granted
    SWUQueueChooseTarget($player, $friendly, "A_friendly_unit_gains_Raid_1_for_this_phase", "LAW_012#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'LAW_012');   // Raid 1 this phase
};

$onAttackAbilities["LAW_012:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUMillTopCard(intval($player));
};
