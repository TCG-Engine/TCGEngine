<?php
// TS26_22
// Cost 4 - The Darksaber - Only the Strongest Shall Rule - [Command,Aggression,Villainy] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains Sentinel. / When Played: If there are 4 or more different keywords among friendly units, ready attached unit.

// TS26_22 The Darksaber (upgrade) — When Played: if there are 4+ different keywords among friendly units,
// ready the attached unit. ($mzID = the host.)
$whenPlayedAbilities["TS26_22:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $found = [];
    // Every keyword counts, via the shared set (GameLogic.php _SWUUnitKeywordSet). This used a hand-kept
    // 10-keyword list that missed Smuggle, Coordinate, Exploit, Piloting, Plot and Support.
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            foreach (_SWUUnitKeywordSet($o) as $kw) $found[$kw] = true;
        }
    }
    if (count($found) >= 4) OnReadyCard(intval($player), $mzID);
};
