<?php
// JTL_009
// Cost 6 - Boba Fett - Any Methods Necessary - [Aggression,Villainy] - Power 4 - HP 7 - Upgrade Power 4 - Upgrade HP 4
// Text: When you deal non-combat damage: You may exhaust this leader. If you do, deal 1 indirect damage to a player. (That player assigns 1 unpreventable damage among their base and units.)
// DeployText: / Attached unit is a leader unit. / When deployed as an upgrade: Deal up to 4 damage divided as you choose among any number of units. /
// Epic Action: If you control 6 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// JTL_009 Boba Fett — resolve the "exhaust this leader → 1 indirect" non-combat-damage reaction.
$customDQHandlers["JTL_009#0"] = function($player, $parts, $lastDecision) {
    $dealer = intval($parts[0] ?? $player);
    RemoveGlobalEffect($dealer, 'SWU_BOBA_009_PENDING');
    if ($lastDecision !== "YES") return;
    if (!_SWULeaderReadyUndeployed($dealer, 'JTL_009')) return;
    _SWUExhaustUndeployedLeader($dealer, 'JTL_009');
    SWUDealIndirectToChosenPlayer($dealer, 1);
};

// JTL_009 Boba Fett — When deployed as an upgrade: Deal up to 4 damage divided as you choose among any
// number of units. MZSPLITASSIGN "up to" mode → the shared SPLIT_DAMAGE resolver.
$whenPlayedAsUpgradeAbilities["JTL_009:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUOfferSplitDamage(intval($player), 4, $targets, "Divide_up_to_4_damage_among_units", true);
};
