<?php
// ASH_064
// Cost 6 - The Armorer - Secrecy is Our Survival - [Vigilance,Heroism] - Power 5 - HP 5
// Text: Shielded (When you play this unit, give a Shield token to her.) / When Played: Give a Shield token to each friendly unit with Shielded (including this one).

// ASH_064 The Armorer — Shielded (auto) + When Played: give a Shield token to each friendly unit with
// Shielded (including this one).
$whenPlayedAbilities["ASH_064:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (empty($u->removed) && HasKeyword_Shielded($u)) {
            $mz = SWUFindMzByUID(intval($u->UniqueID ?? 0));
            if ($mz !== null) DoGiveShieldToken(intval($player), $mz);
        }
    }
};
