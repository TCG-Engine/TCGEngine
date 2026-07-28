<?php
// ASH_007
// Cost 5 - Grand Admiral Sloane - Holding the Empire Together - [Command,Villainy] - Power 4 - HP 5
// Text: Action [Exhaust]: Choose one: / Give each ground unit Sentinel and Overwhelm for this phase. / Give each space unit Sentinel and Overwhelm for this phase.
// DeployText: Overwhelm / Each other friendly unit gains Overwhelm and Sentinel.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ASH_007 Grand Admiral Sloane — Action [Exhaust]: choose one — give each ground unit, OR each space unit,
// Sentinel and Overwhelm for this phase. (Generic SENTINEL/OVERWHELM registry turn-effects, phase duration.)
$leaderAbilities["ASH_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1,
        tooltip: "Give_each_ground_OR_space_unit_Sentinel_and_Overwhelm_this_phase");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_007#0", 1);
};

$customDQHandlers["ASH_007#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    foreach (["my{$arena}", "their{$arena}"] as $z) {   // "each ... unit" = both players' units in that arena
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) {
                AddTurnEffect($mz, 'SENTINEL^ASH_007');
                AddTurnEffect($mz, 'OVERWHELM^ASH_007');
            }
        }
    }
    SWUAfterAction($player);
};
