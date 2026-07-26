<?php
// SHD_262
// Confiscate
// Text: Defeat an upgrade.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_262:0"] = function($player, $mzID = '') {
// Confiscate — "Defeat an upgrade." (mandatory, single)
            SWUQueueDefeatUpgrade(intval($player), 'Choose_a_unit_to_defeat_an_upgrade_on', may: false, max: 1);
            return;
};
