<?php
// TWI_080
// Cost 2 - Poggle the Lesser - Archduke of the Stalgasin Hive - [Command,Villainy] - Power 1 - HP 4
// Text: When you play another unit: You may exhaust this unit. If you do, create a Battle Droid token.

$customDQHandlers["TWI_080#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    OnExhaustCard(intval($player), $mz);
    SWUCreateUnitToken(intval($player), 'TWI_T01');
};
