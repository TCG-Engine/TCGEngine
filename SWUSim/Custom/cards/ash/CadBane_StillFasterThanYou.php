<?php
// ASH_011
// Cost 6 - Cad Bane - Still Faster than You - [Aggression,Villainy] - Power 4 - HP 7
// Text: Action [Exhaust]: Deal 1 damage to a unit with 2 or more remaining HP.
// DeployText: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / On Attack: You may deal 1 damage to a unit with 2 or more remaining HP.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── ASH_011 Cad Bane (deployed Leader Unit) On Attack ───────────────────────
// On Attack: You may deal 1 damage to a unit with 2 or more remaining HP. Same
// "you may ping a unit" shape as SOR_010, restricted to targets that have ≥2
// remaining HP (matching Cad Bane's front-side leader Action filter).
$onAttackAbilities["ASH_011:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) >= 2) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $targets), 0,
        'Deal_1_to_a_unit_with_2+_remaining_HP?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'DEAL_UNIT_DAMAGE|1', 0);
};

// ASH_011 Cad Bane — Action [Exhaust]: deal 1 damage to a unit with 2 or more remaining HP.
$leaderAbilities["ASH_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) >= 2) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_unit_with_2+_remaining_HP", "DEAL_UNIT_DAMAGE|1");
    SWUQueueAfterAction($player);
};
