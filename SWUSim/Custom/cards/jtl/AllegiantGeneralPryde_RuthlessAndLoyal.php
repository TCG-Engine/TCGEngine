<?php
// JTL_133
// Cost 2 - Allegiant General Pryde - Ruthless and Loyal - [Aggression,Villainy] - Power 2 - HP 3
// Text: When indirect damage is dealt to a unit: You may defeat a non-unique upgrade on it. / On Attack: If you have the initiative, deal 2 indirect damage to a player.

// ── JTL_133 Allegiant General Pryde — On Attack: if you have the initiative, deal 2 indirect to a player.
// (The passive "when indirect is dealt to a unit → may defeat a non-unique upgrade on it" reaction lives
// in SWUApplyIndirectAssignment.)
$onAttackAbilities["JTL_133:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (PlayerHasIniative(intval($player))) SWUDealIndirectToChosenPlayer(intval($player), 2, '', _SWUSrcUID($mzID));
};
