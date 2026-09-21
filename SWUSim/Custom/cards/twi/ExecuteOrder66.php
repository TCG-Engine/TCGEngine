<?php
// TWI_239
// Execute Order 66
// Text: Deal 6 damage to each Jedi unit. For each unit defeated this way, its controller creates a Clone Trooper token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_239:0"] = function($player, $mzID = '') {
// Execute Order 66 — "Deal 6 damage to each Jedi unit. For each unit defeated
                          // this way, its controller creates a Clone Trooper token."
            global $playerID;
            $playerID = intval($player);
            // Snapshot each Jedi unit's UID + controller before dealing damage.
            $jedi = [];
            // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
            // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
            // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
            // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
            foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && TraitContains($o, 'Jedi')) {
                        $jedi[] = ['uid' => intval($o->UniqueID ?? 0), 'ctrl' => intval($o->Controller ?? 0)];
                    }
                }
            }
            foreach ($jedi as $j) { $mz = SWUFindMzByUID($j['uid']); if ($mz !== null) SWUDealDamageToUnit($mz, 6, intval($player)); }
            // For each snapshotted Jedi now gone (defeated by the 6), its controller creates a Clone Trooper.
            foreach ($jedi as $j) {
                if (SWUFindMzByUID($j['uid']) === null && $j['ctrl'] > 0) SWUCreateUnitToken($j['ctrl'], 'TWI_T02');
            }
            return;
};
