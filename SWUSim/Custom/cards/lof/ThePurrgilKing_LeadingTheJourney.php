<?php
// LOF_121
// Cost 8 - The Purrgil King - Leading The Journey - [Command] - Power 4 - HP 12
// Text: Restore 4 / When Played: Draw a card for each friendly unit with 7 or more remaining HP.

// LOF_121 The Purrgil King — Restore 4 + When Played: draw a card for each friendly unit with 7+ HP.
$whenPlayedAbilities["LOF_121:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $count = 0;
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) >= 7) $count++;
    }
    if ($count > 0) DoDrawCard(intval($player), $count);
};
