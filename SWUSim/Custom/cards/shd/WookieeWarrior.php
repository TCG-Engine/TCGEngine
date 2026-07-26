<?php
// SHD_249
// Cost 4 - Wookiee Warrior - [Heroism] - Power 2 - HP 5
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: If you control another Wookiee unit, draw a card.

// ─── SHD_249 Wookiee Warrior ──────────────────────────────────────────────────
// Grit (auto) + When Played: If you control another Wookiee unit, draw a card.
$whenPlayedAbilities["SHD_249:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && HasTrait($u->CardID ?? '', 'Wookiee')) {
            DoDrawCard(intval($player), 1);
            return;
        }
    }
};
