<?php
// TWI_200
// Creative Thinking
// Text: Exhaust a non-unique unit. Create a Clone Trooper token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_200:0"] = function($player, $mzID = '') {
// Creative Thinking — "Exhaust a non-unique unit. Create a Clone Trooper token."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !CardUnique($o->CardID ?? '')) $targets[] = $mz;
                }
            }
            if (!empty($targets)) SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_non-unique_unit", "EXHAUST_UNIT");
            SWUCreateUnitToken(intval($player), 'TWI_T02'); // create a Clone Trooper (unconditional)
            return;
};
