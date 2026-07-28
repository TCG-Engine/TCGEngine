<?php
// SHD_252
// Smuggler's Aid
// Text: Heal 3 damage from your base. Smuggle [3 resources Heroism] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_252:0"] = function($player, $mzID = '') {
// Smuggler's Aid — heal 3 damage from your base.
            OnHealBase(intval($player), intval($player), 3);
            return;
};
