<?php
// SHD_039
// Cost 4 - Calculated Lethality - [Vigilance,Villainy]
// Text: Defeat a non-leader unit that costs 3 or less. For each upgrade that was on that unit, give an Experience token to a friendly unit.

// ─── SHD_039 Calculated Lethality (Event) continuation ────────────────────────
// Defeat the chosen ≤3-cost non-leader unit; for each REAL upgrade that was on it, give an Experience
// token to a friendly unit (count upgrades BEFORE the defeat; tokens don't count).
$customDQHandlers["SHD_039#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $n = 0;
    foreach (GetUpgradesOnUnit($o) as $sub) {
        if (strpos(CardType($sub->CardID ?? '') ?? '', 'Upgrade') !== false) $n++;
    }
    SWUDefeatUnit(intval($player), $lastDecision);
    if ($n <= 0) return;
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $fo = GetZoneObject($mz);
            if ($fo !== null && empty($fo->removed)) $friendly[] = $mz;
        }
    }
    if (empty($friendly)) return;
    for ($i = 0; $i < $n; $i++) {
        SWUQueueChooseTarget(intval($player), $friendly, "Give_an_Experience_token_to_a_friendly_unit", "GIVE_EXPERIENCE|1");
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_039:0"] = function($player, $mzID = '') {
// Calculated Lethality — "Defeat a non-leader unit that costs 3 or less. For each
                          // upgrade that was on that unit, give an Experience token to a friendly unit."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_costing_3_or_less", "SHD_039#0");
            return;
};
