<?php
// JTL_234
// Torpedo Barrage
// Text: Deal 5 indirect damage to a player. (They assign 5 unpreventable damage among their base and units.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_234:0"] = function($player, $mzID = '') {
// Torpedo Barrage — Deal 5 indirect damage to a player (you choose; CR §35).
            SWUDealIndirectToChosenPlayer(intval($player), 5);
            return;
};
