<?php
// LAW_147
// Cost 4 - Jaunty Light Freighter - [Command,Heroism] - Power 1 - HP 1
// Text: When Played: Give an Experience token to this unit for each different aspect among units you control.

// LAW_147 Jaunty Light Freighter — When Played: give an Experience token to this unit for each
// different aspect among units you control.
$whenPlayedAbilities["LAW_147:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $aspects = [];
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $aspects[$a] = true; }
    }
    for ($i = 0; $i < count($aspects); $i++) DoGiveExperienceToken(intval($player), $mzID);
};
