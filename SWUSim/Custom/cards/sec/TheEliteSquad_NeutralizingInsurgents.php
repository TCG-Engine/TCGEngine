<?php
// SEC_143
// Cost 8 - The Elite Squad - Neutralizing Insurgents - [Aggression,Villainy] - Power 6 - HP 8
// Text: Grit / When Played/When damage is dealt to this unit: You may deal 2 damage to another <uq> (unique) unit.

$whenPlayedAbilities["SEC_143:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    _SWUSec143Offer(intval($player), SWUObjUID($self, 0));
};
