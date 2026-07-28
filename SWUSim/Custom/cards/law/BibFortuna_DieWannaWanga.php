<?php
// LAW_134
// Cost 2 - Bib Fortuna - Die Wanna Wanga? - [Command,Villainy] - Power 3 - HP 2
// Text: When Played: If you control another Underworld unit, create a Credit token.

// LAW_134 Bib Fortuna — When Played: if you control another Underworld unit, create a Credit token.
$whenPlayedAbilities["LAW_134:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $uid) continue;
        if (TraitContains($u, 'Underworld')) { SWUCreateCreditToken(intval($player), 1); return; }
    }
};
