<?php
// SEC_088
// Cost 7 - First Light - Threatening Elegance - [Command,Villainy] - Power 5 - HP 7
// Text: Ambush / When this unit attacks and defeats a unit: You may draw a card. / Plot

$customDQHandlers["SEC_088#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    DoDrawCard(intval($player), 1);
};
