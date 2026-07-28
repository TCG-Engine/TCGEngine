<?php
// SHD_208
// Final Showdown
// Text: Ready each unit you control. At the start of the regroup phase, you lose the game.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_208:0"] = function($player, $mzID = '') {
// Final Showdown — "Ready each unit you control. At the start of the regroup
                          // phase, you lose the game." Ready every friendly unit now, then arm the
                          // SWU_SHD208_LOSE flag; _SWUCheckFinalShowdownLose (RegroupPhaseStart, before
                          // the draw step) declares the OTHER player the winner. Mirrors SEC_145's
                          // regroup-start win check, inverted to a loss.
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                OnReadyCard(intval($player), $mz);
            }
            AddGlobalEffects(intval($player), 'SWU_SHD208_LOSE');
            return;
};
