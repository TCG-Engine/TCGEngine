<?php
// SHD_215
// Cost 3 - Smuggler's Starfighter - [Cunning] - Power 2 - HP 2
// Text: When Played: If you control another Underworld unit, give an enemy unit -3/-0 for this phase. / Smuggle [4 resources Cunning] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_215 Smuggler's Starfighter ───────────────────────────────────────────
// When Played: If you control ANOTHER Underworld unit, give an enemy unit -3/-0 for this phase.
// Mandatory once gated (registry row 'SHD_215' STAT_DEBUFF); fizzles with no enemy unit.
$whenPlayedAbilities["SHD_215:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $gate = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID
            && HasTrait($u->CardID ?? '', 'Underworld')) { $gate = true; break; }
    }
    if (!$gate) return;
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_an_enemy_unit_-3/-0_this_phase", "APPLY_PHASE_DEBUFF|3|0|SHD_215");
};
