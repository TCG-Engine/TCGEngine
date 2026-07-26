<?php
// SHD_006
// Cost 7 - Jabba the Hutt - His High Exaltedness - [Command,Villainy] - Power 2 - HP 12
// Text: Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 1 resource less."
// DeployText: When Deployed: Another friendly unit captures an enemy non-leader unit. / Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 2 resources less."
// Epic Action: If you control 7 or more resources, deploy this leader.

// ════════════════════════════════════════════════════════════════════════════════
// SHD_006 Jabba the Hutt "His High Exaltedness" — deployed side + shared grant continuation
// ════════════════════════════════════════════════════════════════════════════════
// Shared Bounty-grant continuation. Both the front leader Action (-1) and the deployed Action (-2)
// route here. $lastDecision = the chosen unit's mzID; $parts[0] = the discount the granted Bounty pays
// out. Grants a phase-duration BOUNTY turn-effect token (SHD_006-<amount>) → the bounty.webp badge
// shows AND the custom reward becomes collectible on the bountied unit's defeat (see the granted-bounty
// snapshot in CollectWhenDefeatedTriggers + SWUCollectBounty, GameLogic.php). Both action paths own
// the After Action (the front leader Action and SWUUnitAction both delegate that to the handler).
$customDQHandlers["SHD_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = max(1, intval($parts[0] ?? 1));
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            AddTurnEffect($lastDecision, SWUMakeTurnEffect('SHD_006', [$amount]));
        }
    }
    SWUAfterAction(intval($player));
};

// Deployed Action [Exhaust]: Choose a unit. For this phase it gains "Bounty - The next unit you play
// this phase costs 2 resources less." (Same grant as the front side; discount 2. A deployed Jabba is
// itself a unit, so a valid "choose a unit" target always exists.)
$unitActionCostKind["SHD_006"] = 'exhaust';

$unitAbilities["SHD_006"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUShd006AllUnits(intval($player));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; } // defensive
    SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_give_a_Bounty", "SHD_006#0|2");
};

// When Deployed: Another friendly unit captures an enemy non-leader unit. Mandatory when both a friendly
// non-Jabba unit (the captor) and an enemy non-leader unit (the captive) exist; fizzles otherwise.
// $mzID = the deployed Jabba's mz (excluded from captors — "ANOTHER friendly unit").
$whenPlayedAbilities["SHD_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $jabba = GetZoneObject($mzID);
    $jabbaUID = ($jabba !== null) ? intval($jabba->UniqueID ?? 0) : 0;
    $captors = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $jabbaUID) $captors[] = $mz;
        }
    }
    if (empty($captors)) return; // no "another friendly unit" → fizzle
    SWUQueueChooseTarget(intval($player), $captors, "Choose_a_friendly_unit_to_capture_with", "SHD_006#1");
};

$customDQHandlers["SHD_006#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorMz = $lastDecision ?? '';
    $captor = (!empty($captorMz) && str_contains($captorMz, '-')) ? GetZoneObject($captorMz) : null;
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? 0);
    $captives = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $captives[] = $mz;
        }
    }
    if (empty($captives)) return; // no enemy non-leader unit → fizzle
    SWUQueueChooseTarget(intval($player), $captives, "Choose_an_enemy_non-leader_unit_to_capture", "SHD_006#2|{$captorUID}");
};

$customDQHandlers["SHD_006#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $captiveMz = $lastDecision ?? '';
    if (empty($captiveMz) || !str_contains($captiveMz, '-')) return;
    $captorMz = SWUFindMzByUID($captorUID);   // re-resolve (the captor pick may have shifted indices)
    if ($captorMz === null) return;
    DoCaptureUnit(intval($player), $captorMz, $captiveMz);
};

$leaderAbilities["SHD_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = _SWUShd006AllUnits($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // defensive (affordability requires one)
    SWUQueueChooseTarget($player, $targets, "Choose_a_unit_to_give_a_Bounty", "SHD_006#0|1");
};
