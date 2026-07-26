<?php
// LOF_146
// Cost 4 - Ki-Adi-Mundi - We Must Push On - [Aggression,Heroism] - Power 4 - HP 4
// Text: When Played: You may use the Force (lose your Force token). If you do, draw 2 cards.

// LOF_146 Ki-Adi-Mundi — When Played: may use the Force → draw 2 cards.
$whenPlayedAbilities["LOF_146:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_draw_2_cards?", "LOF_146#0");
};

$customDQHandlers["LOF_146#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    DoDrawCard(intval($player), 2);
};
