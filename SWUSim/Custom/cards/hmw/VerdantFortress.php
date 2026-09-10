<?php
// HMW_126
// Cost 2 - Verdant Fortress - [Command][Heroism] - Upgrade - Trait: Fortification - non-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "Friendly units gain Raid 1 (They get +1/+0 while attacking.)"
//
// FORTIFY needs no code — HMW_126 is in $Fortify_Cards and SWUGetUpgradeValidTargets routes a Fortify
// upgrade to ['myBase-0']. The exact twin of HMW_112 Military Academy with Raid 1 in place of Overwhelm.
//
// Base-hosted and CONTINUOUS: nothing is stored and nothing needs cleaning up when the upgrade leaves.
// GetConditionalKeyword_Raid_Value asks the board on every read, so a unit that enters play later gains
// it too (a constant ability, not a lasting effect), and Confiscating the Fortress ends it immediately.
//
// ⚠ Raid is a NUMERIC keyword, and that is the one real difference from the Academy. CR 7.5.8.b: multiple
// instances of Raid stack and their numerals are added. The Fortress is non-unique, so every copy on a
// friendly base is its own source of "Raid 1" and COUNTS (_SWUCountBaseUpgrades, the HMW_113 Sinister War
// Memorial helper) — two Fortresses make Raid 2, and a unit with printed Raid 2 becomes Raid 3. The
// Academy can use a boolean read because Overwhelm does not stack (CR 7.5.7.b); this card cannot.
//
// ⚠ "FRIENDLY units" is relative to the BASE's controller and spans the TEAM, so in a 2v2 a teammate's
// Fortress grants your units Raid too. It is CONTROL, not ownership: read off the unit's Controller.
//
// SEC_046 Galen Erso (USER RULING 2026-09-10): naming "Verdant Fortress" blanks the upgrade, and naming the
// BASE it is on blanks every "Attached base gains …" clause there — either way that base's copies grant
// nothing, though they stay attached. Both halves live in _SWUFortifyBlanked (GameLogic). A friendly UNIT that
// has lost its abilities gains nothing either, but that is enforced centrally: GetKeyword_Raid_Value
// returns before this delta is added when SWUKeywordSuppressed() holds.
if (!function_exists('_SWUHmw126RaidGrant')) {
    function _SWUHmw126RaidGrant($obj): int {
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return 0;
        $n = 0;
        foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
            $seat = intval($seat);
            if (_SWUFortifyBlanked($seat, 'HMW_126')) continue;   // SEC_046 naming it OR that base
            $n += _SWUCountBaseUpgrades($seat, 'HMW_126');
        }
        return $n;
    }
}
