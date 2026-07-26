<?php
// LOF_045
// Cost 2 - Yaddle - A Chance To Make Things Right - [Vigilance,Heroism] - Power 2 - HP 4
// Text: Restore 1 / On Attack: Each other friendly Jedi unit gains Restore 1 for this phase.

// LOF_045 Yaddle — Restore 1 + On Attack: each other friendly Jedi unit gains Restore 1 for this phase.
$onAttackAbilities["LOF_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        // CardID-based token (not bare 'RESTORE') so the Active Effects popup shows Yaddle's art as
        // the source — registry row 'LOF_045' => GRANT_KEYWORD_VALUE RESTORE amount 1.
        if (HasTrait($o->CardID ?? '', 'Jedi')) AddTurnEffect($mz, 'LOF_045');
    }
};
