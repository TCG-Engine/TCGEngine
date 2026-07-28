<?php
// LOF_203
// Premonition of Doom
// Text: The next time you take the initiative this phase, exhaust all units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_203:0"] = function($player, $mzID = '') {
// Premonition of Doom — "The next time you take the initiative this phase, exhaust
                        // all units." Lingering per-phase flag consumed in SWUTakeInitiative.
            AddGlobalEffects(intval($player), 'SWU_LOF203');
            return;
};
