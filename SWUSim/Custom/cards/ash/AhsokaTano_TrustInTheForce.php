<?php
// ASH_009
// Cost 6 - Ahsoka Tano - Trust in the Force - [Command,Heroism] - Power 5 - HP 6
// Text: Action [Exhaust]: Choose a unit with less power than a friendly unit. It gets +2/+0 for this phase.
// DeployText: Support (When you deploy this leader, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: You may give a unit with less power than this unit +2/+0 for this phase.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ASH_009 Ahsoka Tano — may give a unit with less power than THIS unit +2/+0 for this phase.
$onAttackAbilities["ASH_009:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self === null) return;
    $selfPow = intval(ObjectCurrentPower($self));
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) < $selfPow) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Buff_a_weaker_unit?",
        "Give_+2/+0_to_a_unit_with_less_power_than_this_unit", "APPLY_PHASE_BUFF|2|0|ASH_009");
};

// ASH_009 Ahsoka Tano — Action [Exhaust]: choose a unit with less power than a friendly unit; +2/+0 this
// phase. Offer any unit (either side) whose power is below the highest friendly unit's power.
$leaderAbilities["ASH_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $maxF = -1;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $maxF = max($maxF, intval(ObjectCurrentPower($o)));
        }
    }
    if ($maxF < 0) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) < $maxF) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_+2/+0_to_a_unit_with_less_power_than_a_friendly_unit", "APPLY_PHASE_BUFF|2|0|ASH_009");
    SWUQueueAfterAction($player);
};
