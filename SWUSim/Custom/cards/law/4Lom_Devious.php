<?php
// LAW_065
// Cost 5 - 4-LOM - Devious - [Command,Cunning,Villainy] - Power 4 - HP 5
// Text: When Played: You may attack with a friendly Bounty Hunter unit, even if it's exhausted. It can't attack bases for this attack.

// LAW_065 4-LOM — When Played: you may attack with a friendly Bounty Hunter unit, even if it's
// exhausted. It can't attack bases for this attack (noBases).
$whenPlayedAbilities["LAW_065:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $bh = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Bounty Hunter')) $bh[] = $mz;
    }
    if (empty($bh)) return;
    SWUQueueMayChooseTarget(intval($player), $bh, "Attack_with_a_friendly_Bounty_Hunter_(even_if_exhausted)?", "Choose_a_Bounty_Hunter", "LAW_065#0");
};

$customDQHandlers["LAW_065#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    BeginSWUAttack(intval($player), $lastDecision, true);   // can't attack bases this attack
};
