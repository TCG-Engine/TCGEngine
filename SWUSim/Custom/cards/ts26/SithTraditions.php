<?php
// TS26_52
// Cost 2 - Sith Traditions - [Command,Villainy] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: Give an Experience token to this unit." and "When Defeated: Give an Experience token to a friendly unit."

// TS26_52 Sith Traditions (upgrade) — grants "On Attack: give an Experience token to this unit" and
// "When Defeated: give an Experience token to a friendly unit." On-Attack half fires for the host mzID.
$onAttackAbilities["TS26_52:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $mzID);   // "this unit" = the host
};
