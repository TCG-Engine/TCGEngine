<?php
// JTL_106
// Unity of Purpose
// Text: For each friendly unit with a different name, give each unit you control +1/+1 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_106:0"] = function($player, $mzID = '') {
// Unity of Purpose — for each friendly unit with a DIFFERENT name, give each
                          // unit you control +1/+1 this phase. N = number of distinct names among your
                          // units; every friendly unit gets +N/+N.
            global $playerID;
            $playerID = intval($player);
            // ⚠ TWO DIFFERENT POOLS — the card's two clauses name different sets, and they are only
            // the same set in a 2-player game:
            //   "for each FRIENDLY unit with a different name"  -> team-wide  (the COUNT)
            //   "give each unit YOU CONTROL +1/+1"              -> self-only  (the BUFF)
            $countPool = SWUFriendlyUnits();      // friendly: spans the team in Team Suns
            $buffPool  = SWUControlledUnits();    // you control: always your own units
            if (empty($countPool) && empty($buffPool)) return;
            $names = [];
            foreach ($countPool as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $names[SWUObjectTitle($o)] = true;
            }
            $n = count($names);
            if ($n <= 0) return;
            foreach ($buffPool as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, $n, $n, 'JTL_106');
            }
            return;
};
