<?php
// LOF_241
// Cost 2 - In the Shadows - [Villainy]
// Text: Give an Experience token to each of up to 3 friendly units with Hidden.

// LOF_241 In the Shadows — give one Experience token to each chosen Hidden unit (up to 3).
$customDQHandlers["LOF_241#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_241:0"] = function($player, $mzID = '') {
// In the Shadows — "Give an Experience token to each of up to 3 friendly units
                          // with Hidden."
            global $playerID; $playerID = intval($player);
            $hidden = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasKeyword_Hidden($o)) $hidden[] = $mz;
            }
            if (empty($hidden)) return;
            $max = min(3, count($hidden));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $hidden), 1,
                tooltip: "Give_an_Experience_token_to_each_of_up_to_3_Hidden_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_241#0", 1);
            return;
};
