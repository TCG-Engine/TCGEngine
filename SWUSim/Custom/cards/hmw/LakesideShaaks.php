<?php
// HMW_228 Lakeside Shaaks — Unit (Ground) 4/4, cost 4, [Cunning], Creature.
// "When Played: Ready a friendly resource."
// Mandatory. "friendly" spans the Team Suns team (user ruling 2026-08-26), so the team-aware helper
// offers the own/teammate split when both have an exhausted resource.

$whenPlayedAbilities["HMW_228:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUReadyFriendlyResources(intval($player), 1);
};
