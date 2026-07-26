<?php
// TWI_160
// Cost 2 - Vanguard Droid Bomber - [Aggression] - Power 2 - HP 2
// Text: When Played: If you control another Separatist unit, deal 2 damage to an enemy base.

// TWI_160 Vanguard Droid Bomber — "When Played: If you control another Separatist unit, deal 2 damage to
// an enemy base."
$whenPlayedAbilities["TWI_160:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hasOther = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && HasTrait($u->CardID ?? '', 'Separatist')) { $hasOther = true; break; }
    }
    if ($hasOther) SWUDealDamageToBase(2, OtherPlayer(intval($player)), intval($player));
};
