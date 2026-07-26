<?php
// LOF_201
// Cost 2 - Qui-Gon Jinn's Lightsaber - [Cunning,Heroism] - Upgrade Power 3 - Upgrade HP 1
// Text: Attach to a friendly non-Vehicle unit. / When Played: If attached unit is Qui-Gon Jinn, you may exhaust any number of units with combined cost 6 or less.

// LOF_201 Qui-Gon Jinn's Lightsaber — When Played (as upgrade; $mzID = the HOST): if the attached unit is
// Qui-Gon Jinn, you may exhaust any number of units with combined cost 6 or less.
$whenPlayedAbilities["LOF_201:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (CardTitle($host->CardID ?? '') !== 'Qui-Gon Jinn') return;
    _SWUCombinedBudgetOffer(intval($player), 6, 'cost', 0);
};
