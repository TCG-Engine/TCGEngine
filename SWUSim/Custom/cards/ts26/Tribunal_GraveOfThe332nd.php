<?php
// TS26_36
// Cost 10 - Tribunal - Grave of the 332nd - [Cunning,Vigilance] - Power 6 - HP 8
// Text: This unit costs 2 resources less to play for each other card you played this phase. / When Played: Give each other unit -2/-2 for this phase.

// TS26_36 Tribunal — When Played: give each OTHER unit -2/-2 for this phase (self excluded by UID).
$whenPlayedAbilities["TS26_36:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? -1) : -1;
    // ⚠ DEFER THE DEFEAT CHECK — multi-unit debuff loop (bug #1055 family), and the widest one: this
    // walks ALL FOUR arenas, so a death on the CASTER'S OWN side shifts its indices too, not just the
    // enemy's. SWUApplyPhaseDebuff's default per-unit SWUCheckShrinkDefeats() would remove a killed
    // unit mid-loop and COMPACT that arena, leaving the mzIDs captured above naming different units —
    // everything behind a victim is silently skipped. Apply to all, defeat once after; that is also the
    // rules-correct order ("give each other unit -2/-2" is simultaneous, then state-based defeats
    // resolve together). Same fix as SEC_051 / LAW_101 / TS26_48 / TWI_075.
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -2) !== $selfUID) {
                SWUApplyPhaseDebuff($mz, 2, 2, 'TS26_36', true);
            }
        }
    }
    SWUCheckShrinkDefeats();
};
