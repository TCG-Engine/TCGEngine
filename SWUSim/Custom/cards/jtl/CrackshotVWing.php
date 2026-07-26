<?php
// JTL_158
// Cost 1 - Crackshot V-Wing - [Aggression] - Power 2 - HP 2
// Text: When Played: If you control no other Fighter units, deal 1 damage to this unit.

// ── JTL_158 Crackshot V-Wing — When Played: If you control no other Fighter units, deal 1 to this unit.
$whenPlayedAbilities["JTL_158:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = SWUObjUID($self, 0);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($u->CardID ?? '', 'Fighter')) return;   // controls another Fighter → no self-damage
    }
    SWUDealDamageToUnit($mzID, 1, intval($player));
};
