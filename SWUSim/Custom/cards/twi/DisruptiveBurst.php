<?php
// TWI_075
// Disruptive Burst
// Text: Give each enemy unit -1/-1 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_075:0"] = function($player, $mzID = '') {
// Disruptive Burst — "Give each enemy unit -1/-1 for this phase."
            global $playerID;
            $playerID = intval($player);
            // ⚠ DEFER THE DEFEAT CHECK — multi-unit debuff loop (bug #1055 family). -1/-1 is lethal to
            // a 1-HP unit; SWUApplyPhaseDebuff's default per-unit SWUCheckShrinkDefeats() would remove
            // it mid-loop, COMPACT the arena, and leave every mzID captured above naming a different
            // unit — so units behind the victim are silently skipped. Apply to all, defeat once after.
            // Also the rules-correct order: "give each enemy -1/-1" applies simultaneously, then
            // state-based defeats resolve together. Same fix as SEC_051 / LAW_101 / TS26_48.
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 1, 1, 'TWI_075', true);
                }
            }
            SWUCheckShrinkDefeats();
            return;
};
