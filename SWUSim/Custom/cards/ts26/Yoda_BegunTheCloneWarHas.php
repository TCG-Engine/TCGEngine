<?php
// TS26_14
// Cost 5 - Yoda - Begun, the Clone War Has - [Vigilance,Command,Heroism] - Power 4 - HP 4
// Text: If you control 7 or more resources, this unit costs 2 resources less to play. / When Played/When Defeated: Create a Clone Trooper token and give it Sentinel for this phase.

// TS26_14 Yoda — cost -2 if 7+ resources (cost modifier). When Played/When Defeated: create a Clone
// Trooper token and give it Sentinel for this phase.
$whenPlayedAbilities["TS26_14:0"] = $whenDefeatedAbilities["TS26_14:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Use the BATCH create API with the grant baked in, not create-then-stamp-the-returned-UID: the
    // grant has to survive ASH_094 Moff Jerjerrod's "create twice that number instead", and only
    // SWUCreateUnitTokens carries $turnEffect through the doubling. Stamping the one returned UID left
    // Jerjerrod's second Clone without Sentinel.
    SWUCreateUnitTokens(intval($player), 'TS26_T02', 1, false, 'SENTINEL');   // Sentinel this phase
};
