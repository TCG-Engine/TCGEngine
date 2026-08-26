<?php
// SEC_225
// Cost 7 - Synara San - Harboring a Secret - [Cunning] - Power 7 - HP 7
// Text: Hidden / On Attack: For each friendly unit, ready a friendly resource.

// SEC_225 Synara San — Hidden + On Attack: for each friendly unit, ready a friendly resource.
$onAttackAbilities["SEC_225:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) { if (empty($u->removed)) $n++; }
    // "ready a FRIENDLY resource" — team-wide, and the player picks the split (USER RULING 2026-08-26).
    // Falls through to the old self-only behaviour whenever there is no teammate pool, so Premier and
    // Twin Suns are unchanged.
    SWUReadyFriendlyResources(intval($player), $n);
};
