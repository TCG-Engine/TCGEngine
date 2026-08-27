<?php
// JTL_199
// Cost 3 - Blade Squadron B-Wing - [Cunning,Heroism] - Power 3 - HP 3
// Text: When Played: If another player controls 3 or more exhausted units, give a Shield token to a unit.

// ── JTL_199 Blade Squadron B-Wing — When Played: If another player controls 3+ exhausted units, give a
// Shield token to a unit. ────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_199:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // "If ANOTHER PLAYER controls 3 or more exhausted units" — EXISTENTIAL, and note it says another
    // PLAYER, not another opponent: in a team game a TEAMMATE counts too. GetOpponent() checked one seat
    // (and returns null above seat 2, so the count was 0 for a far-seat caster).
    $cnt = 0;
    foreach (GetLiveSeatsArray() as $seat) {
        if ($seat === intval($player)) continue;
        $c = 0;
        foreach (GetField($seat) as $u) {
            if ($u !== null && empty($u->removed) && intval($u->Status) === 0) $c++; // Status 0 = exhausted
        }
        if ($c > $cnt) $cnt = $c;   // "a player controls 3+" — the best single player, never a sum
    }
    if ($cnt < 3) return;
    GiveTokenUpgrade($player, $mzID, ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_token_to_a_unit"]);
};
