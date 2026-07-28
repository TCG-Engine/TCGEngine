<?php
// TWI_250
// Sword and Shield Maneuver
// Text: Give each friendly Trooper unit Raid 1 for this phase. Give each friendly Jedi unit Sentinel for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_250:0"] = function($player, $mzID = '') {
// Sword and Shield Maneuver — "Give each friendly Trooper unit Raid 1 for this
                          // phase. Give each friendly Jedi unit Sentinel for this phase."
            global $playerID; $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    if (HasTrait($o->CardID ?? '', 'Trooper')) AddTurnEffect($mz, SWUMakeTurnEffect('RAID', [1], SWU_DUR_PHASE, 'TWI_250'));
                    if (TraitContains($o, 'Jedi')) AddTurnEffect($mz, 'SENTINEL');
                }
            }
            return;
};
