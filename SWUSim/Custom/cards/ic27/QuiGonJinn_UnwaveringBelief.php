<?php
// IC27_079
// Cost 5 - Qui-Gon Jinn - Unwavering Belief - [Command,Heroism] - Unit (Ground) 5/5 (unique)
//   Traits: Republic, Force, Jedi
// Text: Sentinel / When Played: Give another friendly unit +2/+2 for this phase.

// Sentinel is auto-wired ($Sentinel_Cards); only the When Played half needs code.
// "another friendly unit" — self-excluded by UniqueID, both arenas, and NO non-leader qualifier, so a
// deployed leader unit is a legal target (AnyUnitFilter). Mandatory, not a "may": with a single legal
// target SWUQueueChooseTarget auto-resolves, and with none it fizzles without a prompt.
// The buff rides the registered STAT_BUFF token 'IC27_079' (registry row in GameLogic), so it expires
// centrally with the phase — no bespoke cleanup.
$whenPlayedAbilities["IC27_079:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $self    = ($mzID !== '') ? GetZoneObject($mzID) : null;
    $selfUID = SWUObjGone($self) ? -1 : intval($self->UniqueID ?? -1);

    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? 0) === $selfUID) continue;   // "another"
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_another_friendly_unit_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|IC27_079");
};
