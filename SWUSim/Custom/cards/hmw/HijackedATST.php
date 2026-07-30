<?php
// HMW_121
// Cost 5 - Hijacked AT-ST - [Command][Heroism] - Unit (Ground) 7/7 - Traits: Rebel, Vehicle, Walker
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.)
//       When Played: This unit doesn't ready during the next regroup phase.
//
// Overwhelm is auto-wired via the generated keyword registry — only the self-targeted skip-regroup marker
// is new. Reuses HMW_095's SWU_SKIP_REGROUP_READY_{uid} marker (a one-shot the regroup ready loop reads and
// consumes), NOT SOR_186's SWU_CANT_READY_ (which would also block explicit mid-phase "ready a unit" effects).
$whenPlayedAbilities["HMW_121:0"] = function($player, $mzID = '') {
    if ($mzID !== '' && !SWUObjGone(GetZoneObject($mzID))) {
        SWUSkipNextRegroupReady($mzID);
    }
};
