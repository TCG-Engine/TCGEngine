<?php
// SHD_161
// Cost 1 - Stolen Landspeeder - [Aggression] - Power 3 - HP 2
// Text: When Played: If you played this unit from your hand, an opponent takes control of it. / Bounty - If you own this unit, play it from your discard pile for free and give an Experience token to it.

// ─── SHD_161 Stolen Landspeeder ───────────────────────────────────────────────
// When Played: If you played this unit from your hand, an opponent takes control of it.
// The hand-source signal is the per-UID SWU_PLAYED_FROM_HAND_ flag set in ActivateCard (only for
// hand plays — the bounty's free discard replay does NOT set it, so control stays put there).
// Take-control is permanent (SOR_122-style, no revert marker).
$whenPlayedAbilities["SHD_161:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FROM_HAND_' . $uid) <= 0) return;
    SWUTakeControlOfUnit(OtherPlayer(intval($player)), $mzID);
};
