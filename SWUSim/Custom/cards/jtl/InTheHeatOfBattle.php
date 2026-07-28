<?php
// JTL_077
// In the Heat of Battle
// Text: Each unit gains Sentinel and loses Saboteur for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_077:0"] = function($player, $mzID = '') {
// In the Heat of Battle — "Each unit gains Sentinel and loses Saboteur for this
                          // phase."
            global $playerID;
            $playerID = intval($player);
            for ($p = 1; $p <= 2; $p++) {
                foreach (array_merge(GetGroundArena($p), GetSpaceArena($p)) as $u) {
                    if (SWUObjGone($u)) continue;
                    $mz = $u->GetMzID();
                    AddTurnEffect($mz, 'JTL_077_SENTINEL');   // gain Sentinel this phase
                    AddTurnEffect($mz, 'JTL_077');            // lose Saboteur this phase (suppressor)
                }
            }
            return;
};
