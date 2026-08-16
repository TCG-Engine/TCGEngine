<?php
// SHD_072
// Cost 2 - Imprisoned - [Vigilance] - Upgrade
// Text: Attach to a non-leader unit. Attached unit loses its current abilities and can't gain abilities.

// ── SHD_072 Imprisoned — OnAttached ───────────────────────────────────────────
// Snapshot which keyword-granting turn effects the host ALREADY had at the moment it was jailed.
//
// Why: "loses its current abilities" only SUPPRESSES what the unit already had — those come back when
// the upgrade leaves. "Can't gain abilities" is the stronger half: anything handed to it WHILE jailed
// must never apply, not even later. Grant effects stamp their token on every unit unconditionally
// (JTL_077 In the Heat of Battle gives EVERY unit Sentinel for the phase) and LostAbilities() only
// suppresses the READ, so without this the token survived and defeating the Imprisoned handed the unit
// an ability it was never allowed to gain.
//
// The purge itself runs on the becomes-unattached seam
// (`_SWUShd072PurgeGrantsGainedWhileJailed` in CombatLogic.php, beside SOR_122 Traitorous's control
// revert — that seam is already documented as being reached by EVERY route that separates an upgrade
// from its host, including a cross-unit move, not just the defeat chokepoint).
// This snapshot is what lets the purge tell the two halves apart: only tokens ABSENT from it are
// removed. Keyed by the host's UniqueID so a shifting mzID cannot mis-target it.
$onAttachedAbilities["SHD_072:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $uid = intval($host->UniqueID ?? 0);
    if ($uid <= 0) return;
    $preFlag = 'SWU_SHD072_PRE_' . $uid . '_';
    SWUClearGlobalEffectsByPrefix(intval($player), $preFlag);   // a re-attach starts a fresh window
    foreach (SWUParsedTurnEffects($host) as $e) {
        if (!in_array(($e['kind'] ?? ''), ['GRANT_KEYWORD', 'GRANT_KEYWORD_VALUE'], true)) continue;
        AddGlobalEffects(intval($player), $preFlag . (string)($e['base'] ?? ''));
    }
};
