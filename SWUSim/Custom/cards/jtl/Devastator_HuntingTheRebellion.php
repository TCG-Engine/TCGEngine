<?php
// JTL_143
// Cost 8 - Devastator - Hunting the Rebellion - [Aggression,Villainy] - Power 9 - HP 6
// Text: You assign all indirect damage you deal to opponents. / When Played: Deal 4 indirect damage to each opponent.

// JTL_143 Devastator — When Played: deal 4 indirect damage to each opponent. (Its passive "You
// assign all indirect damage you deal to opponents" lives in SWUIndirectAssignToOpponentSources,
// so the controller does the assigning — applied automatically by the funnel.)
$whenPlayedAbilities["JTL_143:0"] = function($player, $mzID) {
    SWUDealIndirectToEachOpponent(intval($player), 4);
};
