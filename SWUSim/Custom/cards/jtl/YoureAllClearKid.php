<?php
// JTL_055
// Cost 2 - You're All Clear, Kid - [Vigilance,Heroism]
// Text: Defeat an enemy space unit with 3 or less remaining HP. If you do and an opponent controls no space units, you may give an Experience token to a unit.

// ── JTL_055 You're All Clear, Kid (event continuation) — defeat the chosen space unit; if the opponent
// then controls no space units, may give an Experience token to a unit. ─────────────────────────────
$customDQHandlers["JTL_055#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    if (empty(ZoneSearch("theirSpaceArena", AnyUnitFilter))) {
        $targets = SWUAllUnits();
        if (!empty($targets)) {
            SWUQueueMayChooseTarget(intval($player), $targets,
                "You_may_give_an_Experience_token_to_a_unit", "Give_an_Experience_token", "GIVE_EXPERIENCE|1");
        }
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_055:0"] = function($player, $mzID = '') {
// You're All Clear, Kid — defeat an enemy space unit with 3 or less remaining
                          // HP. If you do and an opponent controls no space units, you may give an
                          // Experience token to a unit (continuation JTL_055).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("theirSpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && (ObjectCurrentHP($o) - intval($o->Damage)) <= 3) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Defeat_an_enemy_space_unit_with_3_or_less_remaining_HP", "JTL_055#0");
            return;
};
