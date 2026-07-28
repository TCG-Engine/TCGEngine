<?php
// ASH_182
// Cost 2 - Unfettered Ambition - [Aggression] - Upgrade Power 1 - Upgrade HP 1
// Text: When Played: For each upgrade on attached unit not named Advantage (including this one), give an Advantage token to attached unit.

// ASH_182 Unfettered Ambition — upgrade (+1/+1). When Played: for each upgrade on attached unit NOT
// named Advantage (including this one), give an Advantage token to attached unit. As a non-pilot upgrade
// its WhenPlayed lands here with $mzID = the HOST unit (CollectWhenPlayedAsUpgradeTriggers fallback).
$whenPlayedAbilities["ASH_182:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $n = 0;
    foreach (GetUpgradesOnUnit($host) as $up) {
        if (($up->CardID ?? '') !== 'ASH_T02') $n++;   // every upgrade not named "Advantage"
    }
    for ($i = 0; $i < $n; $i++) DoGiveAdvantageToken(intval($player), $mzID);
};
