<?php
// SOR_170
// Power Failure
// Text: Defeat any number of upgrades on a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_170:0"] = function($player, $mzID = '') {
// Power Failure — "Defeat any number of upgrades on a unit." (min 0)
            SWUQueueDefeatUpgrade(intval($player), 'Choose_a_unit_to_defeat_upgrades_on', may: false, max: 99, min: 0);
            return;
};
