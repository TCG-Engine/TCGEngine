<?php
// SOR_203
// Cost 4 - Cunning - [Cunning,Cunning]
// Text: Choose two, in any order: / Return a non-leader unit with 4 or less power to its owner's hand. / Give a unit +4/+0 for this phase. / Exhaust up to 2 units. / An opponent discards a random card from their hand.

// SOR_203 Exhaust continuation: exhaust each chosen unit (exhausting doesn't reindex).
$customDQHandlers["SOR_203#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        OnExhaustCard($player, $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_203:0"] = function($player, $mzID = '') {
// Cunning — return a ≤4-power non-leader / +4/+0 this phase / exhaust up to 2 / opponent discards random
            SWUQueueModalChoose(intval($player), 'SOR_203', ['ReturnUnit', 'BuffUnit', 'Exhaust', 'Discard'], 2);
            return;
};
