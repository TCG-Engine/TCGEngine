<?php
// LOF_191
// Cost 1 - BD-1 - Beep Boo Boo Bweep - [Cunning,Heroism] - Power 1 - HP 3
// Text: Hidden (This unit can't be attacked if it was played this phase.) / When Played: Choose another friendly unit. While this unit is in play, the chosen unit gets +1/+0 and gains Saboteur.

// LOF_191 BD-1 — When Played: Choose another friendly unit. While this unit is in play, the chosen unit
// gets +1/+0 and gains Saboteur (link stored as SWU_LOF191_{src}_{tgt}; read by _SWULof191BuffCount,
// which scans by SOURCE UID so a COPIED BD-1 — TS26_34 Fives — is seen too, and counts so repeats stack).
$whenPlayedAbilities["LOF_191:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits(null, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_another_friendly_unit_(it_gets_+1/+0_and_Saboteur)", "LOF_191#0|{$selfUID}");
};

$customDQHandlers["LOF_191#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $srcUID = intval($parts[0] ?? -1);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddGlobalEffects(intval($player), 'SWU_LOF191_' . $srcUID . '_' . intval($o->UniqueID ?? -1));
};
