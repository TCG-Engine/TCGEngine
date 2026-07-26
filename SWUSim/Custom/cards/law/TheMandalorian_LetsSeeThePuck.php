<?php
// LAW_052
// Cost 6 - The Mandalorian - Let's See the Puck - [Vigilance,Aggression] - Power 6 - HP 5
// Text: When Played: Draw a card. / When you draw 1 or more cards during the action phase: Give a Shield token to this unit.

// LAW_052 The Mandalorian — When Played: Draw a card. (The reactive "when you draw 1+ during the action
// phase: Shield this unit" lives in _SWUOnPlayerDrew — the WhenPlayed draw itself self-shields him.)
$whenPlayedAbilities["LAW_052:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};
