<?php
// IBH_009 / IBH_025
// Cost 2 - I've Found Them - [Command]
// Text: Reveal the top 3 cards of your deck. Draw a unit revealed this way, then discard the other revealed cards.

$whenPlayedAbilities["IBH_009:0"] = function($player, $mzID = '') {
    _topDeckSearchBegin(intval($player), 3, fn($c) => CardType($c) === 'Unit', "count:1",
        "IBH_TOPDECK_DISCARD_FINALIZE");
};
$whenPlayedAbilities["IBH_025:0"] = $whenPlayedAbilities["IBH_009:0"];
