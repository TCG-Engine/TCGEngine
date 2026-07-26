<?php
// SEC_053
// One in a Million
// Text: This card can't be played from your hand. Defeat a unit with power and remaining HP both equal to the number of ready resources you control. Plot

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_053:0"] = function($player, $mzID = '') {
// One in a Million — Defeat a unit with power AND remaining HP both equal to
                          // the number of ready resources you control. (Plot event; can't be played
                          // from hand — see _SWUCantPlayFromHand.) Mandatory defeat; fizzles if no
                          // legal target. "a unit" = any unit, both players, all arenas (incl. leaders).
            global $playerID; $playerID = intval($player);
            $n = SWUResourceCount(intval($player), readyOnly: true);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (intval(ObjectCurrentPower($o)) === $n
                    && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) === $n) $targets[] = $mz;
            }
            if (empty($targets)) return;   // no unit matches → mandatory defeat fizzles
            SWUQueueChooseTarget(intval($player), $targets,
                "Defeat_a_unit_(power_and_remaining_HP_=_your_ready_resources)", "DEFEAT_UNIT");
            return;
};
