<?php
// SEC_195
// Cost 2 - Arrest - [Cunning,Villainy]
// Text: Your base captures an enemy non-leader unit. At the start of the regroup phase, its owner rescues it.

// SEC_195 Arrest — the chosen enemy non-leader unit is captured by the base.
$customDQHandlers["SEC_195#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    _SWUBaseCaptureUnit(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_195:0"] = function($player, $mzID = '') {
// Arrest — Your base captures an enemy non-leader unit. At the start of the
                          // regroup phase, its owner rescues it. (Base captive store + RegroupPhaseStart rescue.)
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Your_base_captures_an_enemy_non-leader_unit", "SEC_195#0");
            return;
};
