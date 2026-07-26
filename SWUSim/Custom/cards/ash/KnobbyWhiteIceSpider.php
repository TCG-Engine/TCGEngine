<?php
// ASH_178
// Cost 7 - Knobby White Ice Spider - [Aggression] - Power 5 - HP 7
// Text: Hidden (This unit can't be attacked if it was played this phase.) / When Played: For each enemy unit, give an Advantage token to this unit.

// ── ASH Phase 2 Batch 2.4 ──
// ASH_178 Knobby White Ice Spider — Hidden (auto) + When Played: for each enemy unit, give an Advantage
// token to this unit. Counts enemy units across BOTH arenas (incl. tokens/deployed leaders).
$whenPlayedAbilities["ASH_178:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = SWUAllUnits('their');
    $n = count($enemy);
    for ($i = 0; $i < $n; $i++) DoGiveAdvantageToken(intval($player), $mzID);
};
