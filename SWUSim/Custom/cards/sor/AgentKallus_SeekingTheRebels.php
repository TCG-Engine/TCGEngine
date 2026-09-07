<?php
// SOR_115
// Cost 5 - Agent Kallus - Seeking the Rebels - [Command] - Power 4 - HP 4
// Text: Ambush (After you play this unit, he may ready and attack an enemy unit.) / When another unique unit is defeated: You may draw a card. Use this ability only once each round.

// SOR_115 Agent Kallus — optional draw on the once-per-round defeat trigger.
// ⚠ The round's use is spent HERE, on the accepted YES. USER RULING 2026-09-07: declining a triggered
// "you may" whose whole effect is the optional part never used the ability, so a second unique-unit
// defeat the same round still offers.
// The budget is PER KALLUS UNIT (NumUses on the unit, not a player flag), so $parts[0] carries the
// UniqueID of the Kallus that armed this offer — with two copies in play the wrong one would otherwise
// pay, silently letting one Kallus draw twice and the other never.
$customDQHandlers["SOR_115#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;   // declined → the round is NOT spent
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    if ($uid > 0) {
        $mz = SWUFindMzByUID($uid);
        $ku = ($mz !== null) ? GetZoneObject($mz) : null;
        if ($ku !== null && empty($ku->removed)) SWUConsumeUse($ku);
    }
    DoDrawCard(intval($player), 1);
};
