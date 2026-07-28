<?php
// LAW_088
// Cost 2 - Anakin Skywalker - Prescient Podracer - [Cunning,Vigilance,Heroism] - Power 2 - HP 4
// Text: When a friendly unit's attack ends: If no other units have attacked this phase, you may return it to its owner's hand. If you do, heal 2 damage from your base.

// LAW_088 Anakin Skywalker (field passive) — "When a friendly unit's attack ends: if no other units
// have attacked this phase, you may return it to its owner's hand. If you do, heal 2 from your base."
// Wired in SWUCollectCombatHitTriggers (any friendly attacker). The handler offers the YESNO.
$customDQHandlers["LAW_088#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null && SWUBounceUnit(intval($player), $mz)) OnHealBase(intval($player), intval($player), 2);
};
