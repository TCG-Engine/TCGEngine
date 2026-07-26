<?php
// SHD_225
// Cost 2 - Jetpack - [Cunning] - Upgrade Power 2 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / When Played: Give a Shield token to attached unit. At the start of the regroup phase, defeat that token. / Smuggle [4 resources Cunning]

// ─── SHD_225 Jetpack ──────────────────────────────────────────────────────────
// When Played: Give a Shield token to attached unit. At the start of the regroup phase, defeat
// that token (per-grant flag keyed by host UID, consumed in RegroupPhaseStart). Non-pilot upgrade
// When Played → $mzID = the HOST unit.
$whenPlayedAbilities["SHD_225:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    DoGiveShieldToken(intval($player), $mzID);
    AddGlobalEffects(intval($player), 'SWU_SHD225_TOKEN_' . intval($host->UniqueID ?? 0));
};
