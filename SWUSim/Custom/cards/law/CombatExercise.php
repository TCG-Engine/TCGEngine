<?php
// LAW_165
// Cost 1 - Combat Exercise - [Command]
// Text: Exhaust a friendly unit. If you do, give 2 Experience tokens to it.

// LAW_165 Combat Exercise — exhaust the chosen friendly unit and give it 2 Experience tokens.
$customDQHandlers["LAW_165#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnExhaustCard(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_165:0"] = function($player, $mzID = '') {
// Combat Exercise — "Exhaust a friendly unit. If you do, give 2 Experience
                          // tokens to it." Offer ready friendly units (exhaustable).
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit_(give_it_2_Experience)", "LAW_165#0");
            return;
};
