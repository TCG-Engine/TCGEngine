<?php
// ASH_079
// Cost 4 - Koska Reeves - Warrior of Mandalore - [Vigilance] - Power 4 - HP 4
// Text: While you control a token unit, this unit gains Sentinel. / When Played: If a friendly unit was defeated this phase, create a Mandalorian token.

// ASH_079 Koska Reeves — When Played: if a friendly unit was defeated this phase, create a Mandalorian
// token. (Conditional Sentinel passive lives in KeywordEffects.) Uses the SWU_FRIENDLY_DEFEATED flag.
$whenPlayedAbilities["ASH_079:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0) SWUCreateUnitToken(intval($player), 'ASH_T01');
};
