<?php
// LAW_176
// Cost 3 - Sebulba's Podracer - Taking the Lead - [Aggression,Villainy] - Power 3 - HP 3
// Text: When you discard a card from your deck: You may ready this unit. Use this ability only once each round.

// LAW_176 Sebulba's Podracer — resolve the may-ready (once each round, per copy: NumUses on the Podracer,
// refilled at regroup; spent only on an accepted YES).
$customDQHandlers["LAW_176#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    $o = GetZoneObject($mz);
    if (SWUObjGone($o) || !SWUHasUseAvailable($o)) return;
    OnReadyCard(intval($player), $mz);
    SWUConsumeUse($o);
};
