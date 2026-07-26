<?php
// SEC_235
// The Wrong Ride
// Text: Exhaust 2 enemy resources. Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_235:0"] = function($player, $mzID = '') {
// The Wrong Ride — Exhaust 2 enemy resources.
            global $playerID; $playerID = intval($player);
            SWUExhaustResources(OtherPlayer(intval($player)), 2, true); // exhaust up to 2 (as many as ready)
            return;
};
