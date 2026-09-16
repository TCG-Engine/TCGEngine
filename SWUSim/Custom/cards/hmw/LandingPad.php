<?php
// HMW_271 Landing Pad — Upgrade, cost 3, no aspects, Fortification.
// "Fortify (Attach this to your base, not a unit.) Attached base gains: 'Friendly space units get +1/+0.'"
// FORTIFY needs no code (it is in $Fortify_Cards). The grant is HMW_126 Verdant Fortress's shape with a stat
// bonus in place of Raid: continuous, read live, each copy on each friendly base (own + team) adds +1/+0.
// "space units" = units currently IN the space arena (a unit that moved counts where it is now).
// SEC_046 Galen Erso naming the Landing Pad or the base blanks that base's copies (_SWUFortifyBlanked).

if (!function_exists('_SWUHmw271SpacePower')) {
    function _SWUHmw271SpacePower($obj): int {
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return 0;
        $uid = intval($obj->UniqueID ?? 0);
        $inSpace = false;
        foreach (GetSpaceArena($ctrl) as $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? -1) === $uid) { $inSpace = true; break; }
        }
        if (!$inSpace) return 0;
        $n = 0;
        foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
            $seat = intval($seat);
            if (_SWUFortifyBlanked($seat, 'HMW_271')) continue;
            $n += _SWUCountBaseUpgrades($seat, 'HMW_271');
        }
        return $n;
    }
}
