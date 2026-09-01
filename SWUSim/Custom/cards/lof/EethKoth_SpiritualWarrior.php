<?php
// LOF_097
// Cost 4 - Eeth Koth - Spiritual Warrior - [Command,Heroism] - Power 5 - HP 4
// Text: When Defeated: You may use the Force. If you do, put this card into play as a resource.

// LOF_097 Eeth Koth — When Defeated: may use the Force → put this card into play as a resource (exhausted).
$whenDefeatedAbilities["LOF_097:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_put_Eeth_Koth_into_play_as_a_resource?", "LOF_097#0");
};

$customDQHandlers["LOF_097#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $mz = _SWUFindSelfInDiscardMzID(intval($player), 'LOF_097'); // already in discard when WhenDefeated resolves
    if ($mz !== null) SWURampResourceExhausted(intval($player), $mz); // enters exhausted (no "ready" wording)
};
