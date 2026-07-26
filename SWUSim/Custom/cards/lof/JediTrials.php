<?php
// LOF_052
// Cost 1 - Jedi Trials - [Vigilance,Heroism] - Upgrade Power 0 - Upgrade HP 0
// Text: Attach to a Force unit. / Attached unit gains: "On Attack: Give an Experience token to this unit." / While attached unit has 4 or more upgrades on it, it gains the Jedi trait.

// LOF_052 Jedi Trials — attached unit gains "On Attack: give an Experience token to this unit."
// (⚠ "While attached unit has 4+ upgrades, it gains the Jedi trait" not implemented — per-instance trait grant.)
$onAttackAbilities["LOF_052:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $mzID); // "this unit" = the host
};
