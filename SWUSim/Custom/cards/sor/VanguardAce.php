<?php
// SOR_191
// Cost 2 - Vanguard Ace - [Cunning,Heroism] - Power 1 - HP 1
// Text: When Played: For each other card you played this phase, give an Experience token to this unit.

// SOR_191 Vanguard Ace — "When Played: For each other card you played this phase, give an Experience
// token to this unit." Counter includes Vanguard itself, so "other" = count - 1.
$whenPlayedAbilities["SOR_191:0"] = function($player, $mzID) {
    $others = max(0, GlobalEffectCount(intval($player), 'SWU_CARDS_PLAYED') - 1);
    for ($i = 0; $i < $others; $i++) DoGiveExperienceToken(intval($player), $mzID);
};
