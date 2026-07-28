<?php
// ASH_149
// Cost 8 - Eviscerator - Burn Them Away - [Aggression,Villainy] - Power 9 - HP 7
// Text: Advantage tokens on friendly units lose all abilities. (They aren't defeated after combat.) / When Played/On Attack: Give 2 Advantage tokens to each other friendly unit.

// ASH_149 Eviscerator — passive "Advantage tokens on friendly units aren't defeated after combat" (gated
// in _SWUDefeatAllAdvantageTokens / _SWUResolveAdvantageShed) + When Played/On Attack: give 2 Advantage
// tokens to each OTHER friendly unit.
$whenPlayedAbilities["ASH_149:0"] =
$onAttackAbilities["ASH_149:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) {
                DoGiveAdvantageToken(intval($player), $mz);
                DoGiveAdvantageToken(intval($player), $mz);
            }
        }
    }
};
