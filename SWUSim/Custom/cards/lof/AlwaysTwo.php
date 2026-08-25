<?php
// LOF_042
// Cost 4 - Always Two - [Vigilance,Villainy]
// Text: Choose 2 friendly <uq> Sith units. If you do, give 2 Shield tokens and 2 Experience tokens to each chosen unit. Defeat all other friendly units.

// LOF_042 Always Two — the MZMULTICHOOSE delivered exactly 2 chosen friendly Sith units. Give each 2
// Shield + 2 Experience tokens, then defeat all OTHER friendly units (the chosen survive).
$customDQHandlers["LOF_042#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    $chosen = array_values(array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS'));
    if (count($chosen) < 2) return; // didn't actually choose 2 → fizzle
    $chosenUIDs = [];
    foreach ($chosen as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $chosenUIDs[] = intval($o->UniqueID ?? -1);
        DoGiveShieldToken(intval($player), $mz);
        DoGiveShieldToken(intval($player), $mz);
        DoGiveExperienceToken(intval($player), $mz);
        DoGiveExperienceToken(intval($player), $mz);
    }
    // Defeat all OTHER friendly units (snapshot mzIDs first; defeats mutate the zones).
    $others = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (in_array(intval($o->UniqueID ?? -1), $chosenUIDs, true)) continue;
        $others[] = intval($o->UniqueID ?? -1);
    }
    foreach ($others as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDefeatUnit(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_042:0"] = function($player, $mzID = '') {
// Always Two — "Choose 2 friendly <uq> Sith units. If you do, give 2 Shield tokens
                          // and 2 Experience tokens to each chosen unit. Defeat all other friendly units."
                          // Only UNIQUE (<uq>) Sith are selectable. "Defeat all OTHER friendly units" runs even
                          // when you can't choose 2 (none spared → ALL friendly units are defeated).
            global $playerID; $playerID = intval($player);
            $sith = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Sith') && CardUnique($o->CardID ?? '')) $sith[] = $mz;
            }
            if (count($sith) < 2) {
                // Fewer than 2 unique Sith → cannot spare 2 → "all OTHER friendly units" = ALL of them.
                $allUids = [];
                foreach (SWUFriendlyUnits() as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $allUids[] = intval($o->UniqueID ?? -1);
                }
                foreach ($allUids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null && $mz !== '') SWUDefeatUnit(intval($player), $mz); }
                return;
            }
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "2|2|" . implode('&', $sith), 1,
                tooltip: "Choose_2_friendly_unique_Sith_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_042#0", 1);
            return;
};
