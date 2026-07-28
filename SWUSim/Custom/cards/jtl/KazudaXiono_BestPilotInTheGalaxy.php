<?php
// JTL_018
// Cost 4 - Kazuda Xiono - Best Pilot in the Galaxy - [Cunning,Heroism] - Power 2 - HP 5 - Upgrade Power 3 - Upgrade HP 3
// Text: Action [Exhaust]: A friendly unit loses all abilities for this round. Take an extra action after this one.
// DeployText: On Attack: Choose any number of friendly units. They lose all abilities for this round. / Attached unit is a leader unit. It gains: "On Attack: Choose any number of friendly units. They lose all abilities for this round." /
// Epic Action: If you control 4 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// ── JTL_018 Kazuda Xiono — leader action continuation: apply the lose-all-abilities token to the chosen
// friendly unit, then take an EXTRA action (no turn swap). The token expires at round end like JTL_244.
$customDQHandlers["JTL_018#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== '' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) { AddTurnEffect($lastDecision, 'JTL_018'); _SWUCheckDefeatAfterAbilityLoss($lastDecision); }
    }
    SWUAfterActionExtra(intval($player)); // "Take an extra action after this one."
};

// Deploy On Attack: "Choose any number of friendly units. They lose all abilities for this round."
// (Combat owns the after-action — no SWU_AFTER_ACTION here.)
$onAttackAbilities["JTL_018:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('my');
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . count($targets) . "|" . implode("&", $targets), 1,
        tooltip: "Choose_friendly_units_to_lose_all_abilities_this_round");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_018#1", 1);
};

$customDQHandlers["JTL_018#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || $lastDecision === '-' || $lastDecision === '') return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) { AddTurnEffect($mz, 'JTL_018'); _SWUCheckDefeatAfterAbilityLoss($mz); }
    }
};

// JTL_018 Kazuda Xiono — Leader Action [Exhaust]: A friendly unit loses all abilities for this round.
// Take an extra action after this one. The extra action is always granted (even with no friendly unit);
// continuation in CardDQHandlers.php ("JTL_018") applies the lose-abilities token then ends WITHOUT
// swapping the turn player (SWUAfterActionExtra).
$leaderAbilities["JTL_018"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) { SWUAfterActionExtra($player); return; } // no unit → still take the extra action
    SWUQueueChooseTarget($player, $friendly,
        "A_friendly_unit_loses_all_abilities_this_round", "JTL_018#0");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_244:0"] = function($player, $mzID = '') {
// There Is No Escape — "Choose up to 3 units. Those units lose all abilities and
                          // can't gain abilities for this round."
            global $playerID;
            $playerID = intval($player);
            $units = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            ));
            if (empty($units)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . implode("&", $units), 1, "Choose_up_to_3_units_to_lose_abilities");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_244#0", 1);
            return;
};
