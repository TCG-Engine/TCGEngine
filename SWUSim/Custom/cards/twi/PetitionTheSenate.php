<?php
// TWI_100
// Petition the Senate
// Text: If you control 3 or more Official units, draw 3 cards.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_100:0"] = function($player, $mzID = '') {
// Petition the Senate — "If you control 3 or more Official units, draw 3 cards."
            global $playerID; $playerID = intval($player);
            $n = 0;
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Official')) $n++;
                }
            }
            if ($n >= 3) DoDrawCard(intval($player), 3);
            return;
};
