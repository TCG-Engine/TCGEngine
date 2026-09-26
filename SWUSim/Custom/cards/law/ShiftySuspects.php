<?php
// LAW_197
// Cost 4 - Shifty Suspects - [Aggression] - Power 4 - HP 5
// Text: On Attack: Bases can't be healed for this phase.

// LAW_197 Shifty Suspects — On Attack: bases can't be healed for this phase. (Reuses SOR_160's
// SWU_NOHEAL_BASE global lock, cleared at RegroupPhaseStart.)
$onAttackAbilities["LAW_197:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE');
    // Companion marker so a blocked heal can name what stopped it. The lock check counts
    // 'SWU_NOHEAL_BASE' by EXACT match, so this is invisible to it, and RegroupPhaseStart clears by
    // PREFIX, so it is swept with the flag.
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE_SRC_LAW_197');
};
