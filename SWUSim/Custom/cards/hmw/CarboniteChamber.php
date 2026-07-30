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
// The cost is "defeat this upgrade", which changes game state, so per CR 6.4.587.c the Action stays
// available even with no unit to choose: the cost is paid and the effect fizzles.
$baseUpgradeAbilities["HMW_095"] = function(int $player, int $upgradeIndex): void {
    global $playerID; $playerID = $player;

    // Pay the cost first — the upgrade defeats itself.
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
