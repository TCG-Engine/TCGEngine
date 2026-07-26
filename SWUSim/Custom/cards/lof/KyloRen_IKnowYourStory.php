<?php
// LOF_229
// Cost 2 - Kylo Ren - I Know Your Story - [Villainy] - Power 2 - HP 3
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When you play an upgrade on this unit: You may use the Force (lose your Force token). If you do, draw a card.

$customDQHandlers["LOF_229#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    DoDrawCard(intval($player), 1);
};
