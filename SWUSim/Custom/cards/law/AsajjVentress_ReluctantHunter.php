<?php
// LAW_061
// Cost 5 - Asajj Ventress - Reluctant Hunter - [Command,Aggression] - Power 3 - HP 3
// Text: When Played: You may ready another Bounty Hunter unit.

// LAW_061 Asajj Ventress — When Played: you may ready another Bounty Hunter unit.
$whenPlayedAbilities["LAW_061:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $bh = [];
    // "Ready ANOTHER Bounty Hunter unit" — no "friendly" qualifier, so an ENEMY Bounty Hunter is a legal
    // target too (and deployed BH leaders, which live in the arena zones, qualify). Search all four arenas.
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $uid) continue;
        if (TraitContains($o, 'Bounty Hunter')) $bh[] = $mz;
    }
    if (empty($bh)) return;
    SWUQueueMayChooseTarget(intval($player), $bh, "Ready_another_Bounty_Hunter_unit?", "Choose_a_Bounty_Hunter", "READY_UNIT");
};
