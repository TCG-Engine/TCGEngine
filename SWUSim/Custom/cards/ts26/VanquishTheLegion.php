<?php
// TS26_48
// Cost 4 - Vanquish the Legion - [Vigilance]
// Text: Give each enemy ground unit -2/-2 for this phase.

// ⚠ DEFER THE DEFEAT CHECK — this is a MULTI-UNIT debuff loop (bug #1055, game 850132).
// SWUApplyPhaseDebuff runs SWUCheckShrinkDefeats() per unit by default. When one of these units is
// killed by its own -2/-2, it is removed mid-loop and the arena COMPACTS — so every mzID captured by
// the ZoneSearch above now names a different unit, and the ones BEHIND the victim are silently skipped.
// Live: P1's ground was [0] Jabba [1] Qi'ra [2] Boba Fett; the -2/-2 killed Qi'ra, Boba slid into slot 1
// past the cursor, and only Boba went undebuffed.
// Passing $deferDefeatCheck = true applies every debuff first and defeats once, afterwards — which is
// also the rules-correct order: "give each enemy -2/-2" applies simultaneously, then state-based
// defeats resolve together, so no defeat reaction can fire between two applications.
// Same fix and same reason as SEC_051 Bo-Katan and LAW_101 Lawbringer (2026-07-28); this card was
// written afterwards and reintroduced the pattern.
$whenPlayedAbilities["TS26_48:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    foreach (ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 2, 2, 'TS26_48', true);
    }
    SWUCheckShrinkDefeats();
};
