<?php
// JTL_083
// Cost 2 - Pantoran Starship Thief - [Command,Villainy] - Power 2 - HP 2 - Upgrade Power 0 - Upgrade HP 0
// Text: When Played: You may pay 3 resources. If you do, attach this unit as an upgrade to a Fighter or Transport unit without a Pilot on it. Take control of that unit. / When this upgrade detaches from a unit: That unit's owner takes control of it.

// JTL_083 Pantoran Starship Thief — "When Played: You may pay 3 resources. If you do, attach this unit as
// an upgrade to a Fighter or Transport unit without a Pilot on it. Take control of that unit." The detach-
// returns-control half is handled at the SWUDefeatUpgrade chokepoint (shared with SOR_122).
$whenPlayedAbilities["JTL_083:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    if (SWUResourceCount(intval($player), true) < 3) return; // can't pay → no offer
    $uid = intval($self->UniqueID ?? 0);
    // Fighter/Transport units (any owner) without a Pilot already on them.
    $targets = [];
    foreach (['myGroundArena','mySpaceArena','theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (!HasTrait($o->CardID ?? '', 'Fighter') && !HasTrait($o->CardID ?? '', 'Transport')) continue;
            if (_SWUFindPilotSubcard($o) !== null) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Pay_3_to_attach_to_and_take_control_of_a_Fighter/Transport", "Choose_a_Fighter_or_Transport", "JTL_083#0|" . $uid);
};

$customDQHandlers["JTL_083#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 3) return;
    $uid = intval($parts[0] ?? 0);
    $selfMz = SWUFindMzByUID($uid);
    if ($selfMz === null) return;
    $hostObj = GetZoneObject($lastDecision);
    if (SWUObjGone($hostObj)) return;
    $hostUid = intval($hostObj->UniqueID ?? 0);
    SWUPayCost(intval($player), 3, 0, false);   // effect cost ("pay 3 to attach and take control"), not halved by JTL_105
    SWUMoveUnitToUpgrade($selfMz, $lastDecision, true);  // attach Pantoran Thief as a Pilot
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz !== null) SWUTakeControlOfUnit(intval($player), $hostMz); // take control of the host
};
