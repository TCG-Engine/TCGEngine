<?php
// SHD_113
// Cost 2 - Privateer Crew - [Command] - Power 2 - HP 2
// Text: Smuggle [6 resources, command] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.) / When played using Smuggle: Give 3 Experience tokens to this unit.

// ─── SHD_113 Privateer Crew ────────────────────────────────────────────────────
// When played using Smuggle: Give 3 Experience tokens to this unit. Dispatched from the
// SWUSmuggleResource smuggle-trigger seam (synchronous, no decision).
$whenPlayedUsingSmuggleAbilities["SHD_113:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    for ($i = 0; $i < 3; $i++) DoGiveExperienceToken(intval($player), $mzID);
};
