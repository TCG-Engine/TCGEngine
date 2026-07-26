<?php
// TS26_22
// Cost 4 - The Darksaber - Only the Strongest Shall Rule - [Command,Aggression,Villainy] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains Sentinel. / When Played: If there are 4 or more different keywords among friendly units, ready attached unit.

// TS26_22 The Darksaber (upgrade) — When Played: if there are 4+ different keywords among friendly units,
// ready the attached unit. ($mzID = the host.)
$whenPlayedAbilities["TS26_22:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $found = [];
    $boolKw = ['Sentinel', 'Ambush', 'Overwhelm', 'Grit', 'Saboteur', 'Shielded', 'Hidden', 'Bounty'];
    $valKw  = ['Raid', 'Restore'];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            foreach ($boolKw as $kw) { $fn = "HasKeyword_{$kw}"; if (function_exists($fn) && $fn($o)) $found[$kw] = true; }
            foreach ($valKw as $kw)  { $fn = "GetKeyword_{$kw}_Value"; if (function_exists($fn) && intval($fn($o) ?? 0) > 0) $found[$kw] = true; }
        }
    }
    if (count($found) >= 4) OnReadyCard(intval($player), $mzID);
};
