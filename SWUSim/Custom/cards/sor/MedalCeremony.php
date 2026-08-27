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
                          // that attacked this phase." NO "friendly" in the text: enemy Rebel attackers
                          // are legal targets too (candidate #6 fix, 2026-08-14). The SWU_ATTACKED_{uid}
                          // flag is stamped on the ATTACKER'S CONTROLLER's seat, so read BOTH seats —
                          // UIDs are globally unique, and a caster-seat-only read misses every enemy
                          // attacker (and a control-change since the attack).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (!TraitContains($o, 'Rebel')) continue;
                $uid = intval($o->UniqueID ?? 0);
                if (GlobalEffectCount(1, 'SWU_ATTACKED_' . $uid) <= 0
                    && GlobalEffectCount(2, 'SWU_ATTACKED_' . $uid) <= 0) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;  // no eligible Rebel attacker → fizzle
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . implode("&", $targets), 1, tooltip:"Give_Experience_to_up_to_3_Rebel_units_that_attacked_this_phase");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_245#0", 1, dontSkipOnPass: 1);
            return;
};
