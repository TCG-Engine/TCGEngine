<?php
// LOF_098
// Cost 5 - Leia Organa - Extraordinary - [Command,Heroism] - Power 5 - HP 5
// Text: While this unit is in the space arena, she can't ready and gains: "Action [use the Force]: Move this unit to the ground arena and give each friendly Heroism unit +2/+2 for this phase."

// LOF_098 — While in the SPACE arena, gains "Action [use the Force]: Move this unit to the ground arena
// and give each friendly Heroism unit +2/+2 for this phase." Cost kind 'none': no exhaust and no ready
// requirement (she's stuck exhausted in space — "can't ready" there — and spends the Force to escape).
// Availability + Force check live in SWUUnitActionAffordable (case 'LOF_098', space-arena-gated).
$unitActionCostKind["LOF_098"] = 'none';

$unitAbilities["LOF_098"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    UseTheForce(intval($player));
    SWUMoveUnitBetweenArenas($mzID, 'GroundArena');   // preserves damage/upgrades/UID
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits(null, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Heroism') !== false) {
            AddTurnEffect($mz, SWUMakeTurnEffect('SWUBUFF', [2, 2], SWU_DUR_PHASE)); // +2/+2 this phase (incl. herself)
        }
    }
    SWUAfterAction($player);
};
