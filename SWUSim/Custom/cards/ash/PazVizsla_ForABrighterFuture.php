<?php
// ASH_028
// Cost 5 - Paz Vizsla - For a Brighter Future - [Vigilance,Command,Heroism] - Power 4 - HP 7
// Text: Sentinel / When Defeated: If this unit wasn't defeated by combat damage, create 2 Mandalorian tokens.

// ASH_028 Paz Vizsla — Sentinel (auto) + When Defeated: if this unit WASN'T defeated by combat damage,
// create 2 Mandalorian tokens. The combat-vs-effect distinction is the SWU_COMBATDEF_ snapshot.
$whenDefeatedAbilities["ASH_028:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $fromCombat = !empty($GLOBALS['gCombatDefeatByMz'][$mzID] ?? false);
    unset($GLOBALS['gCombatDefeatByMz'][$mzID]);
    if ($fromCombat) return;   // defeated by combat damage → no tokens
    SWUCreateUnitTokens(intval($player), 'ASH_T01', 2);
};
