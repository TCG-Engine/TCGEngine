<?php
// SHD_174
// Cost 1 - Hotshot DL-44 Blaster - [Aggression] - Upgrade Power 2 - Upgrade HP 0
// Text: Attach to a non-VEHICLE unit. / Smuggle [3 resources, cunning] / When played using Smuggle: Attack with attached unit.

// ─── SHD_174 Hotshot DL-44 Blaster ────────────────────────────────────────────
// When played using Smuggle: Attack with attached unit. Receives the HOST mz from SMUGGLE_ATTACH;
// only a ready host can attack — otherwise close the action cleanly. BeginSWUAttack owns the
// after-action once it fires.
$whenPlayedUsingSmuggleAbilities["SHD_174:0"] = function($player, $hostMz) {
    global $playerID; $playerID = intval($player);
    $h = GetZoneObject($hostMz);
    if (SWUObjGone($h) || intval($h->Status ?? 0) !== 1) {
        SWUAfterAction(intval($player));
        return;
    }
    BeginSWUAttack(intval($player), $hostMz);
};
