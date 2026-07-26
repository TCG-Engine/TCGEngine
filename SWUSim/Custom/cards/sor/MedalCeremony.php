<?php
// SOR_245
// Cost 0 - Medal Ceremony - [Heroism]
// Text: Give an Experience token to each of up to 3 REBEL units that attacked this phase.

// SOR_245 Medal Ceremony — give an Experience token to each chosen (up to 3) Rebel attacker.
$customDQHandlers["SOR_245#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === "" || $mz === "-" || $mz === "PASS") continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_245:0"] = function($player, $mzID = '') {
// Medal Ceremony — "Give an Experience token to each of up to 3 Rebel units
                          // that attacked this phase." Read the caster's SWU_ATTACKED_{uid} flags and
                          // keep only in-play Rebel units (the flag is per attacking player).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (!HasTrait($o->CardID, 'Rebel')) continue;
                $uid = intval($o->UniqueID ?? 0);
                if (GlobalEffectCount(intval($player), 'SWU_ATTACKED_' . $uid) <= 0) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;  // no eligible Rebel attacker → fizzle
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . implode("&", $targets), 1, tooltip:"Give_Experience_to_up_to_3_Rebel_units_that_attacked_this_phase");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_245#0", 1);
            return;
};
