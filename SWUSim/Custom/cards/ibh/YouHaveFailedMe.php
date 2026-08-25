<?php
// IBH_095
// Cost 4 - You Have Failed Me - [Aggression,Villainy]
// Text: Defeat a friendly unit. If you do, ready a friendly unit with 5 or less power.

$whenPlayedAbilities["IBH_095:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // "Defeat a FRIENDLY unit" — team-wide in Team Suns. USER RULING (2026-08-25): you may defeat a
    // TEAMMATE's unit to satisfy the "If you do". Both clauses of this card read "friendly", so both
    // span the team; the ready clause below uses side => 'friendly' for the same reason.
    $friendlies = SWUFriendlyUnits();
    if (empty($friendlies)) return;
    SWUQueueChooseTarget(intval($player), $friendlies, "Defeat_a_friendly_unit", "IBH_095#0");
};

// IBH_095 You Have Failed Me — defeat the chosen friendly; then ready a friendly unit with 5 or less
// power (the defeated one is gone; recompute the candidate list live).
$customDQHandlers["IBH_095#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'READY_UNIT', 'side' => 'friendly',
        'extraFilter' => fn($o) => ObjectCurrentPower($o) <= 5,
        'prompt' => "Ready_a_friendly_unit_with_5_or_less_power",
    ]);
};
