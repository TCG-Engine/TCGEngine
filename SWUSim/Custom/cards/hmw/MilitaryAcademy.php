<?php
// HMW_112
// Cost 1 - Military Academy - [Command][Villainy] - Upgrade - Trait: Fortification - non-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "Friendly units gain Overwhelm."
//
// FORTIFY needs no code — HMW_112 is in $Fortify_Cards and SWUGetUpgradeValidTargets routes a Fortify
// upgrade to ['myBase-0']. Same as HMW_205 Intelligence Agency.
//
// The grant is a BASE-HOSTED CONTINUOUS ability, so there is no state to store and nothing to clean up
// when the upgrade leaves: HasConditionalKeyword_Overwhelm asks the board, live, on every read. Defeat
// the upgrade and the Overwhelm is gone on the next query (GrantDiesWithTheUpgrade).
//
// ⚠ "FRIENDLY units" is relative to the BASE'S controller, and friendly spans the TEAM — so in a 2v2 a
// teammate's units gain Overwhelm from your Academy too. Reading it as "units you control" would be the
// self-only trap this set keeps presenting (GiveTokenUpgrade's friendlyOnly, JTL_219's hand-rolled
// ZoneSearch, SWUControlledUnits). Pinned by TeamSuns_ATeammatesUnitsGainOverwhelm.
//
// Non-unique, so a player can field several — but Overwhelm is a BOOLEAN keyword, so a second copy adds
// nothing. That is a real difference from HMW_145 Origin Tree Shyyyo, whose numeric discount stacks;
// no per-copy counting is wanted here.
if (!function_exists('_SWUHmw112GrantsOverwhelm')) {
    function _SWUHmw112GrantsOverwhelm($obj): bool {
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return false;
        if (_SWUBaseHasUpgrade($ctrl, 'HMW_112')) return true;
        foreach (SWUTeammatesOf($ctrl) as $mate) {
            if (_SWUBaseHasUpgrade(intval($mate), 'HMW_112')) return true;
        }
        return false;
    }
}
