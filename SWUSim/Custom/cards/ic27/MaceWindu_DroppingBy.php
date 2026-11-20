<?php
// IC27_081
// Cost 6 - Mace Windu - Dropping By - [Command,Heroism] - Power 5 - HP 5 - Force, Jedi, Republic
// Text: Ambush / On Attack: Give an Experience token to this unit.

// IC27_081 Mace Windu — Ambush (printed keyword, generic) + On Attack: give an Experience token to this unit. LAW_037 Han
// Solo's clause word for word. The On Attack window resolves before combat damage, so the token's +1/+1 counts for the
// attack that gave it.
$onAttackAbilities["IC27_081:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $mzID);
};
