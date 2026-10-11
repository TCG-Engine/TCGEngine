<?php
// LOF_045
// Cost 2 - Yaddle - A Chance To Make Things Right - [Vigilance,Heroism] - Power 2 - HP 4
// Text: Restore 1 / On Attack: Each other friendly Jedi unit gains Restore 1 for this phase.

// LOF_045 Yaddle — Restore 1 + On Attack: each other friendly Jedi unit gains Restore 1 for this phase.
$onAttackAbilities["LOF_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        // CardID-based token (not bare 'RESTORE') so the Active Effects popup shows Yaddle's art as
        // the source — registry row 'LOF_045' => GRANT_KEYWORD_VALUE RESTORE amount 1.
        if (TraitContains($o, 'Jedi')) AddTurnEffect($mz, 'LOF_045');
    }
};
