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
    // "AN opponent takes control of it" — the ability's controller chooses which. Auto-resolves to an
    // invisible PASSPARAMETER at one eligible opponent, so Premier is byte-identical (I1).
    // ⚠ NO $eligible filter: every live opponent can take control of a ground unit.
    //   Near-miss worth knowing: SWUTakeControlOfUnit returns '' when SWUAvoidsTakeControl blocks it
    //   (LAW_149 Rey). That is a property of the UNIT, not of any opponent, so it does not vary per seat
    //   and must NOT become a per-opponent gate.
    // ⚠ Carried by UID: the pick is interactive and the arena can reindex before the continuation runs.
    SWUQueueChooseOpponent(intval($player), 'SHD_161#0|' . $uid,
        "Choose_an_opponent_to_take_control_of_it");
};

$customDQHandlers["SHD_161#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $opp = SWUPickedOpponent($lastDecision);
    if ($uid <= 0 || $opp <= 0 || $opp === intval($player)) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUTakeControlOfUnit($opp, $mz);
};
