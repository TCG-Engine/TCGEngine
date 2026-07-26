<?php
// SOR_049
// Cost 6 - Obi-Wan Kenobi - Following Fate - [Vigilance,Heroism] - Power 4 - HP 6
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Defeated: Give 2 Experience tokens to another friendly unit. If it's a Force unit, draw a card.

// SOR_049 Obi-Wan Kenobi — When Defeated: 2 Experience to another friendly unit; if it's a Force unit, draw.
$whenDefeatedAbilities["SOR_049:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;            // self is removed (being defeated) → excluded
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_to_another_friendly_unit", "SOR_049#0");
};

$customDQHandlers["SOR_049#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed) && TraitContains($o, 'Force')) DoDrawCard(intval($player), 1);
};
