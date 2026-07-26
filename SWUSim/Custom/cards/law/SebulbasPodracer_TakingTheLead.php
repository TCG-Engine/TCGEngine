<?php
// LAW_176
// Cost 3 - Sebulba's Podracer - Taking the Lead - [Aggression,Villainy] - Power 3 - HP 3
// Text: When you discard a card from your deck: You may ready this unit. Use this ability only once each round.

// LAW_176 Sebulba's Podracer — resolve the may-ready (once each round; SWU_LAW176_USED cleared at regroup).
$customDQHandlers["LAW_176#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_LAW176_USED') > 0) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    OnReadyCard(intval($player), $mz);
    AddGlobalEffects(intval($player), 'SWU_LAW176_USED');
};
