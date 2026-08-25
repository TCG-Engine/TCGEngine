<?php
// SEC_125
// Reconnaissance
// Text: If you control a ground unit and a space unit, draw 2 cards.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_125:0"] = function($player, $mzID = '') {
// Reconnaissance — if you control a ground unit AND a space unit, draw 2 cards.
            global $playerID; $playerID = intval($player);
            $hasG = count(SWUControlledUnits('Ground')) > 0;   // "If YOU CONTROL a ground unit and a space unit"
            $hasS = count(SWUControlledUnits('Space')) > 0;
            if ($hasG && $hasS) DoDrawCard(intval($player), 2);
            return;
};
