<?php
// TS26_55
// Cost 5 - Jedi General - [Command] - Power 2 - HP 3
// Text: Ambush / When Played: For each Republic leader you control (as a leader or unit), create a Clone Trooper token and give an Experience token to it.

// TS26_55 Jedi General — Ambush (keyword). When Played: for each Republic leader you control (as a
// leader or unit), create a Clone Trooper token and give an Experience token to it.
$whenPlayedAbilities["TS26_55:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetLeader(intval($player)) as $l) {
        if ($l !== null && HasTrait($l->CardID ?? '', 'Republic')) $n++;
    }
    for ($i = 0; $i < $n; $i++) {
        $uid = SWUCreateUnitToken(intval($player), 'TS26_T02');
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) DoGiveExperienceToken(intval($player), $mz);
    }
};
