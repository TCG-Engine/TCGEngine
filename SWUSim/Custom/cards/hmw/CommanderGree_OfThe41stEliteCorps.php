<?php
// HMW_138 Commander Gree — Unit (Ground) 3/6, cost 4, [Command], Republic/Clone/Trooper.
// "While there are 3 or more Command aspect icons among friendly units (including this one) and upgrades,
//  this unit gains Raid 4."
// The Raid half lives in GetConditionalKeyword_Raid_Value (KeywordEffects). The count is ICONS, not cards:
// a double-[Command] card adds 2. "friendly" spans the Team Suns team. A friendly upgrade is one a friendly
// player CONTROLS, wherever it is attached — an enemy unit or a base included.

if (!function_exists('_SWUHmw138CommandIcons')) {
    function _SWUHmw138CommandIcons(int $ctrl): int {
        if ($ctrl <= 0) return 0;
        $team = array_map('intval', array_merge([$ctrl], SWUTeammatesOf($ctrl)));
        $icons = fn(string $cid) => count(array_filter(SWUCardAspectIcons($cid), fn($a) => $a === 'Command'));
        $n = 0;
        foreach (GetLiveSeatsArray() as $seat) {
            $seat = intval($seat);
            $hosts = GetUnitsInPlay($seat);
            $base = GetBase($seat)[0] ?? null;
            if ($base !== null) $hosts[] = $base;
            foreach ($hosts as $h) {
                if (!empty($h->removed)) continue;
                $isUnit = ($h !== $base);
                if ($isUnit && in_array(intval($h->Controller ?? $seat), $team, true)) $n += $icons((string)($h->CardID ?? ''));
                foreach (GetUpgradesOnUnit($h) as $up) {
                    $upCtrl = intval($up->Controller ?? 0);
                    if ($upCtrl <= 0) $upCtrl = intval($up->Owner ?? 0);
                    if ($upCtrl <= 0) $upCtrl = $isUnit ? intval($h->Controller ?? $seat) : $seat;
                    if (in_array($upCtrl, $team, true)) $n += $icons((string)($up->CardID ?? ''));
                }
            }
        }
        return $n;
    }
}
