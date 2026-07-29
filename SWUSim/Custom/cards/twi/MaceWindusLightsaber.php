<?php
// TWI_152
// Cost 2 - Mace Windu's Lightsaber - [Aggression,Heroism] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / When Played: If attached unit is Mace Windu, draw 2 cards.

// TWI_152 Mace Windu's Lightsaber — "When Played: If attached unit is Mace Windu, draw 2 cards." (Upgrade;
// $mzID = the host. Non-Vehicle attach handled in SWUGetUpgradeValidTargets.)
$whenPlayedAbilities["TWI_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (SWUObjectTitle($host) === 'Mace Windu') DoDrawCard(intval($player), 2);
};
