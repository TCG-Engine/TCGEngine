<?php
// JTL_135
// Cost 2 - Special Forces TIE Fighter - [Aggression,Villainy] - Power 2 - HP 3
// Text: When Played: If an opponent controls more space units than you, ready this unit.

// ── JTL_135 Special Forces TIE Fighter — When Played: If an opponent controls more space units than
// you, ready this unit. ──────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_135:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $mine = count(SWUControlledUnits('Space'));   // "more space units than YOU"; includes the just-entered unit
    $opp  = count(ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if ($opp > $mine) OnReadyCard(intval($player), $mzID);
};
