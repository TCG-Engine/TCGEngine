<?php
// LAW_256
// Cost 6 - Fire Across the Galaxy - [Heroism]
// Text: Use any number of "When Played" abilities on friendly Spectre units.

// LAW_256 Fire Across the Galaxy — re-resolve the chosen friendly Spectre units' When-Played abilities.
// UID-safe (re-resolve each mzID before firing, since a prior ability may have shifted arena indices);
// the answer is validated against the same Spectre + has-When-Played predicate the offer used.
$customDQHandlers["LAW_256#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!TraitContains($o, 'Spectre') || !HasWhenPlayedAbility($o->CardID ?? '')) continue; // validate the pick
        $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        if ($uid <= 0) continue;
        $mz = SWUFindMzByUID($uid);
        if ($mz === null) continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        OnWhenPlayed(intval($player), $o->CardID ?? '', $mz);  // re-resolve its When-Played
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_256:0"] = function($player, $mzID = '') {
// Fire Across the Galaxy — "Use any number of 'When Played' abilities on friendly
                          // Spectre units." Re-resolve each chosen unit's When-Played (OnWhenPlayed); they
                          // queue FIFO, so each single-decision ability resolves before the next.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    if (TraitContains($o, 'Spectre') && HasWhenPlayedAbility($o->CardID ?? '')) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;   // no friendly Spectre unit has a When-Played ability → fizzle
            $max = count($targets);
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
                "0|{$max}|" . implode('&', $targets), 1,
                tooltip: "Use_any_number_of_friendly_Spectre_When_Played_abilities");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_256#0", 1);
            return;
};
