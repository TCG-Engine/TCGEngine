<?php
// SOR_251
// Confiscate
// Text: Defeat an upgrade.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_251:0"] = function($player, $mzID = '') {
// Confiscate — "Defeat an upgrade." (mandatory, single)
            SWUQueueDefeatUpgrade(intval($player), 'Defeat_an_upgrade', may: false, max: 1);
            return;
};
