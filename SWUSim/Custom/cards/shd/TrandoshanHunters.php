<?php
// SHD_140
// Cost 5 - Trandoshan Hunters - [Villainy,Aggression] - Power 6 - HP 4
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: If an enemy unit has a Bounty, give an Experience token to this unit.

// ─── SHD_140 Trandoshan Hunters ───────────────────────────────────────────────
// Overwhelm (auto) + When Played: If an enemy unit has a Bounty, give an Experience token to this unit.
$whenPlayedAbilities["SHD_140:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $has = false;
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && ObjectHasBounty($o) > 0) { $has = true; break 2; }
        }
    }
    if ($has) DoGiveExperienceToken(intval($player), $mzID);
};
