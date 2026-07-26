<?php
// TWI_185
// Cost 5 - Ziro the Hutt - Colorful Schemer - [Cunning,Villainy] - Power 2 - HP 8
// Text: When Played: For each opponent, you may exhaust a unit that player controls. / On Attack: For each opponent, you may exhaust a resource that player controls.

// TWI_185 Ziro the Hutt — "When Played: For each opponent, you may exhaust a unit that player controls.
// On Attack: For each opponent, you may exhaust a resource that player controls." (2-player: one opponent.)
$whenPlayedAbilities["TWI_185:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SeatCountForGame() <= 2) {
        // 2-player: the one opponent — a single "may exhaust an enemy unit" prompt (unchanged).
        $enemies = [];
        foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $enemies[] = $mz;
            }
        }
        if (!empty($enemies)) SWUQueueMayChooseTarget(intval($player), $enemies,
            "You_may_exhaust_an_enemy_unit", "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
        return;
    }
    // Twin Suns: "For each opponent, you may exhaust a unit THAT player controls" — a separate optional
    // prompt per opponent, scoped to that opponent's own units (p{opp} mzIDs). Sequential (block 1 FIFO).
    foreach (OpponentsOf(intval($player)) as $opp) {
        $enemies = [];
        foreach (["p{$opp}GroundArena", "p{$opp}SpaceArena"] as $z) {
            $zone = GetZone($z);
            for ($i = 0; $i < count($zone); $i++) {
                $o = $zone[$i];
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $enemies[] = "{$z}-{$i}";
            }
        }
        if (!empty($enemies)) SWUQueueMayChooseTarget(intval($player), $enemies,
            "P{$opp}:_you_may_exhaust_a_unit", "Exhaust_a_unit_P{$opp}_controls", "EXHAUST_UNIT");
    }
};

$onAttackAbilities["TWI_185:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "For each opponent, you may exhaust a resource that player controls." One YESNO per opponent that
    // has a ready resource (2-player → the one opponent → byte-identical).
    $multi = SeatCountForGame() > 2;
    foreach (OpponentsOf(intval($player)) as $opp) {
        if (SWUResourceCount($opp, true) <= 0) continue; // no ready resource for this opponent
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
            tooltip: $multi ? "P{$opp}:_exhaust_a_resource_they_control?" : "Exhaust_a_resource_an_opponent_controls?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_185#0|{$opp}", 1);
    }
    // Combat owns the after-action.
};

$customDQHandlers["TWI_185#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    SWUExhaustResources($opp, 1); // exhaust one of that opponent's resources
};
