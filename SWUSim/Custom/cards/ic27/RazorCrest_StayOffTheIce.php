<?php
// IC27_172
// Cost 3 - Razor Crest - Stay Off the Ice - [Cunning] - Unit (Space) 2/5
// Traits: Mandalorian, Vehicle, Transport - Unique
// Text: Each unit (including friendly units) loses all "When Defeated" abilities.
//
// A constant ability over the whole table (every live seat), read where the abilities would be used:
//   • CollectWhenDefeatedTriggers (GameLogic.php) — a defeated unit has no When Defeated of any kind: printed,
//     upgrade-granted, phase-granted, field-granted (ASH_063, TWI_001, SHD_001) and Support-lent.
//   • CollectEntryTriggers — a "When Played/When Defeated" ability IS a When Defeated ability (CR: a slash ability is
//     each of the abilities it names), so it is lost whole and a unit entering under a Razor Crest makes no When Played.
//     All 27 such cards are units whose only When Played is the slash one.
// Last Known Information: a Razor Crest leaving play in the SAME defeat still applies — in combat it is in the defeat
// batch, inside a wipe it is in the window's pre-effect snapshot (SWUSimulDefeatBegin). A blanked Razor Crest does
// nothing (the snapshot already leaves out blanked units).
// Bounty is a keyword resolved in the same window as When Defeated abilities (CR 13.c), not one of them — the engine
// already treats it so for JTL_002 Thrawn / JTL_032 Krennic — so it is untouched.
// Tests: SWUSim/Tests/Cases/ic27/RazorCrest_StayOffTheIce.md

// Is a Razor Crest's ability in force for a defeat whose batch is $batch (CollectWhenDefeatedTriggers' entries)?
function _SWUIc27172Active(array $batch = []): bool {
    if (!empty($GLOBALS['gSimulDefeatWindow']) && isset($GLOBALS['gSimulDefeatUnits'])) {
        foreach ($GLOBALS['gSimulDefeatUnits'] as $cids) {
            if (intval($cids['IC27_172'] ?? 0) > 0) return true;
        }
    }
    foreach (GetLiveSeatsArray() as $seat) {
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_172') continue;
            if (!LostAbilities($u)) return true;
        }
    }
    foreach ($batch as $d) {
        if (($d['cardID'] ?? '') !== 'IC27_172') continue;
        $o = !empty($d['mzID']) ? GetZoneObject($d['mzID']) : null;
        if ($o === null || !LostAbilities($o)) return true;
    }
    return false;
}

// Does a Razor Crest strip $cardID's When Played as it enters? Only a "When Played/When Defeated" ability is lost.
function _SWUIc27172StripsWhenPlayed(string $cardID): bool {
    global $textData;
    if (strpos((string)($textData[$cardID] ?? ''), 'When Played/When Defeated') === false) return false;
    return _SWUIc27172Active();
}
