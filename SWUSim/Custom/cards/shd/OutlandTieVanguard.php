<?php
// SHD_082
// Cost 2 - Outland TIE Vanguard - [Command,Villainy] - Power 2 - HP 1
// Text: When Played: You may give an Experience token to another unit that costs 3 or less.

// ─── SHD_082 Outland TIE Vanguard ─────────────────────────────────────────────
// When Played: You may give an Experience token to another unit that costs 3 or less.
$whenPlayedAbilities["SHD_082:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
                && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_another_unit_costing_3_or_less?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};
