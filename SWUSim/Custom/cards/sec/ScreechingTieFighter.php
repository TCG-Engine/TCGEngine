<?php
// SEC_185
// Cost 1 - Screeching TIE Fighter - [Cunning,Villainy] - Power 2 - HP 1
// Text: On Attack: You may choose a ground unit. If you do, it loses its keywords (and can't gain keywords) for this phase.

// SEC_185 TIE/ln Fighter — On Attack: you may choose a GROUND unit. If you do, it loses its keywords
// (and can't gain keywords) for this phase. (In-combat OnAttack → MZMAYCHOOSE, the proven choose.)
$onAttackAbilities["SEC_185:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $grounds = SWUAllUnits(null, GroundArena);
    if (empty($grounds)) return;
    SWUQueueMayChooseTarget(intval($player), $grounds, "Make_a_ground_unit_lose_its_keywords?", "Choose_a_ground_unit", "SEC_185#0");
};

$customDQHandlers["SEC_185#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    AddTurnEffect($lastDecision, 'SEC_185');   // suppresses ALL keywords this phase (keywordSuppressors ['*'])
};
