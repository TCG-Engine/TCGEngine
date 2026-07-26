<?php
// IBH_023
// Cost 4 - General Rieekan - Stalwart Tactician - [Command,Heroism] - Power 2 - HP 6
// Text: Action [Exhaust]: Attack with another Heroism unit. It gets +2/+0 for this attack.  (You can only attack with a ready friendly unit.)

// IBH_023 / IBH_036 General Rieekan — Action [Exhaust]: attack with another Heroism unit; it gets +2/+0
// for this attack. Reuses IBH_021#0 (+2/+0 then BeginSWUAttack, which owns the after-action).
$unitAbilities["IBH_023"] =
$unitAbilities["IBH_036"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue; // "another" unit
            if (strpos((string)CardAspect($u->CardID ?? ''), 'Heroism') === false) continue;
            $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $units, "Attack_with_another_Heroism_unit_(+2/+0)", "IBH_021#0");
};
