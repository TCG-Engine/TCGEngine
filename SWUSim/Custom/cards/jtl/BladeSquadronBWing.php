<?php
// JTL_199
// Cost 3 - Blade Squadron B-Wing - [Cunning,Heroism] - Power 3 - HP 3
// Text: When Played: If another player controls 3 or more exhausted units, give a Shield token to a unit.

// ── JTL_199 Blade Squadron B-Wing — When Played: If another player controls 3+ exhausted units, give a
// Shield token to a unit. ────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_199:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = GetOpponent(intval($player));
    $cnt = 0;
    foreach (GetField($opp) as $u) {
        if ($u !== null && empty($u->removed) && intval($u->Status) === 0) $cnt++; // Status 0 = exhausted
    }
    if ($cnt < 3) return;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_unit", "GIVE_SHIELD");
};
