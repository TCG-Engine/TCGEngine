<?php
// LOF_054
// Cost 2 - Calm in the Storm - [Vigilance,Heroism]
// Text: Exhaust a friendly unit. If you do, give a Shield token and 2 Experience tokens to it.

// LOF_054 Calm in the Storm — exhaust the chosen friendly unit, then give it a Shield + 2 Experience.
$customDQHandlers["LOF_054#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $o->Status = 0; // exhaust
    DoGiveShieldToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_054:0"] = function($player, $mzID = '') {
// Calm in the Storm — "Exhaust a friendly unit. If you do, give a Shield token
                          // and 2 Experience tokens to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit_(Shield_+_2_Experience)", "LOF_054#0");
            return;
};
