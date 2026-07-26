<?php
// SEC_248
// Cost 1 - B2EMO - That's Two Lies - [Heroism] - Power 0 - HP 4
// Text: Restore 1 / On Attack: You may disclose HeroismHeroism (reveal cards from your hand with these aspect icons among them). If you do, give a unit Sentinel for this phase.

// SEC_248 B2EMO — Restore 1 (auto) + On Attack: you may disclose HeroismHeroism → give a unit Sentinel
// for this phase.
$onAttackAbilities["SEC_248:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Heroism', 'Heroism'], "SEC_248#0",
        "Disclose_HeroismHeroism_to_give_a_unit_Sentinel");
};

$customDQHandlers["SEC_248#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_Sentinel_this_phase", "GRANT_PHASE_KEYWORD|SENTINEL^SEC_248");
};
