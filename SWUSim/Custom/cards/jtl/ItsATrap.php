<?php
// JTL_209
// It's a Trap
// Text: If an opponent controls more space units than you, ready each space unit you control.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_209:0"] = function($player, $mzID = '') {
// It's a Trap — if an opponent controls more space units than you, ready each
                          // space unit you control.
            global $playerID;
            $playerID = intval($player);
            $mine = SWUControlledUnits('Space');   // "each space unit YOU CONTROL"
            if (count(ZoneSearch("theirSpaceArena", AnyUnitFilter)) <= count($mine)) return;
            foreach ($mine as $mz) OnReadyCard(intval($player), $mz);
            return;
};
