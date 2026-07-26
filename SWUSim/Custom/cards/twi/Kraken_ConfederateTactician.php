<?php
// TWI_084
// Cost 5 - Kraken - Confederate Tactician - [Command,Villainy] - Power 2 - HP 5
// Text: When Played: Create 2 Battle Droid tokens. / On Attack: Give each friendly token unit +1/+1 for this phase.

// TWI_084 Kraken — "When Played: Create 2 Battle Droid tokens.
//                   On Attack: Give each friendly token unit +1/+1 for this phase."
$whenPlayedAbilities["TWI_084:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T01', 2); // Battle Droid (Ground, 1/1)
};

$onAttackAbilities["TWI_084:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Token Unit']) as $tmz) {
            $o = GetZoneObject($tmz);
            if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($tmz, 1, 1, 'TWI_084');
        }
    }
    // Combat owns the after-action; no SWUAfterAction here.
};
