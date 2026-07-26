<?php
// SHD_193
// Cost 3 - Frozen in Carbonite - [Cunning,Villainy] - Upgrade Power 0 - Upgrade HP 0
// Text: Attach to a non-leader unit. / Attached unit can't ready. / When Played: Exhaust attached unit.

// ─── SHD_193 Frozen in Carbonite (When Played as upgrade) ─────────────────────
// When Played: Exhaust attached unit. ($mzID = the host.) The "can't ready" passive lives in
// OnReadyCard + the regroup ready-skip (GameLogic).
$whenPlayedAbilities["SHD_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $host->Status = 0;   // exhaust attached unit
};
