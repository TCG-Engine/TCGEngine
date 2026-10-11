<?php
// LOF_121
// Cost 8 - The Purrgil King - Leading The Journey - [Command] - Power 4 - HP 12
// Text: Restore 4 / When Played: Draw a card for each friendly unit with 7 or more remaining HP.

// LOF_121 The Purrgil King — Restore 4 + When Played: draw a card for each friendly unit with 7+ HP.
$whenPlayedAbilities["LOF_121:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $count = 0;
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) >= 7) $count++;
    }
    if ($count > 0) DoDrawCard(intval($player), $count);
};
