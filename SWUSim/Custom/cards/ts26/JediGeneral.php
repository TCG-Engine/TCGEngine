<?php
// TS26_55
// Cost 5 - Jedi General - [Command] - Power 2 - HP 3
// Text: Ambush / When Played: For each Republic leader you control (as a leader or unit), create a Clone Trooper token and give an Experience token to it.

// TS26_55 Jedi General — Ambush (keyword). When Played: for each Republic leader you control (as a
// leader or unit), create a Clone Trooper token and give an Experience token to it.
$whenPlayedAbilities["TS26_55:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "For each Republic leader you control (AS A LEADER OR UNIT)" — the parenthetical is load-bearing.
    // Scanning only the Leader zone missed a unit that IS a leader unit by grant (ASH_135 The Darksaber:
    // "attached unit is a leader unit"), which is exactly the case the parenthetical exists for.
    // A DEPLOYED leader stays in the Leader zone in this engine, so it is still counted by the first
    // loop and must not be counted twice by the second — hence the IsLeaderUnit + not-a-printed-Leader
    // filter on the arena scan.
    $n = 0;
    foreach (GetLeader(intval($player)) as $l) {
        if ($l !== null && HasTrait($l->CardID ?? '', 'Republic')) $n++;
    }
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if ($u === null || !empty($u->removed)) continue;
        if (strpos(CardType($u->CardID ?? '') ?? '', 'Leader') !== false) continue;  // already counted above
        if (!IsLeaderUnit($u)) continue;
        if (!TraitContains($u, 'Republic')) continue;
        $n++;
    }
    // Batch create with the Experience rider attached, not create-then-stamp-the-returned-UID: the rider
    // has to survive ASH_094 Moff Jerjerrod's "create twice that number instead", which makes its extra
    // tokens later, inside its own decision handler. Stamping here left those doubled Clones bare.
    if ($n > 0) SWUCreateUnitTokens(intval($player), 'TS26_T02', $n, false, '', 'EXPERIENCE');
};
