<?php
// JTL_077
// In the Heat of Battle
// Text: Each unit gains Sentinel and loses Saboteur for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_077:0"] = function($player, $mzID = '') {
// In the Heat of Battle — "Each unit gains Sentinel and loses Saboteur for this
                          // phase."
            global $playerID;
            $playerID = intval($player);
            // ⚠ "EACH unit" is the WHOLE TABLE. This used to loop seats 1..2 and take each unit's
            // $u->GetMzID() — and BOTH halves were wrong at 3+ seats, which is why widening the loop
            // alone did not fix it:
            //   • the loop skipped seats 3-4 outright;
            //   • GetMzID() is STRUCTURALLY TWO-SEAT. It returns "my…" or "their…" based on $playerID,
            //     so a seat-3 unit yields "theirGroundArena-N" — which resolves to SEAT 2. Widening the
            //     loop without changing this would have applied seat 3's effect to seat 2's unit.
            // SWUAllUnits() is the sanctioned fan-out: it goes through ZoneSearch('team'/'their'), which
            // expands across every live opponent and returns real p{n}<Zone>-{i} mzIDs at 3+ seats, and
            // is byte-identical to the old my+their merge at two seats.
            foreach (SWUAllUnits() as $mz) {
                $u = GetZoneObject($mz);
                if (SWUObjGone($u)) continue;
                AddTurnEffect($mz, 'JTL_077_SENTINEL');   // gain Sentinel this phase
                AddTurnEffect($mz, 'JTL_077');            // lose Saboteur this phase (suppressor)
            }
            return;
};
