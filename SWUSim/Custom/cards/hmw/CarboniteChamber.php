<?php
// HMW_095
// Cost 1 - Carbonite Chamber - [Vigilance] - Upgrade - Trait: Fortification
// Text: Fortify (Attach this to your base, not a unit.)
//       Action [defeat this upgrade]: Choose a non-Vehicle unit. It doesn't ready during the next
//       regroup phase.
//
// The first ACTIVATED ability hosted on a base, so it registers in $baseUpgradeAbilities (keyed by the
// upgrade's CardID) and is reached by clicking the base — see _SWUBaseActionProviders, which also raises
// a chooser when the base has its own Epic Action available too. It does NOT touch the base's Epic slot.
//
// The cost "defeat this upgrade" changes the game state, so the Action stays available even with no unit to
// choose — the cost is paid and the effect fizzles (guarded by Action_NoLegalTargetStillPaysTheCost).
$baseUpgradeAbilities["HMW_095"] = function(int $player, int $upgradeIndex): void {
    global $playerID; $playerID = $player;

    // Pay the cost — the upgrade defeats itself. HMW_060 Vice Admiral Rampart MAY replace this defeat
    // (defeat Rampart instead): per the SWU CR replacement rules a replacement effect can replace a COST,
    // and the cost still counts as paid as long as the replacement resolves — so this stays replaceable
    // (default $skipReplacement=false). User-confirmed ruling (2026-07-30); HMW/Homeworlds CR unreleased.
    SWUDefeatUpgrade($player, 'myBase-0', $upgradeIndex);

    // "a non-Vehicle unit" carries no friendly/enemy qualifier, so either side's units are legal.
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (TraitContains($o, 'Vehicle')) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets,
        "Choose_a_non-Vehicle_unit_(it_won't_ready_next_regroup)", 'HMW_095#0');
};

$customDQHandlers["HMW_095#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision) && !SWUObjGone(GetZoneObject($lastDecision))) {
        SWUSkipNextRegroupReady($lastDecision);
    }
    SWUAfterAction(intval($player));
};
