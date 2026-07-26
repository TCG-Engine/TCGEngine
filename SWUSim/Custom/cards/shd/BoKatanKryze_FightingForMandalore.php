<?php
// SHD_157
// Cost 2 - Bo-Katan Kryze - Fighting For Mandalore - [Aggression,Aggression] - Power 3 - HP 3
// Text: When Defeated: For each player with 15 or more damage on their base, draw a card.

// ─── SHD_157 Bo-Katan Kryze (When Defeated) ───────────────────────────────────
// When Defeated: For each player with 15 or more damage on their base, draw a card.
$whenDefeatedAbilities["SHD_157:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $count = 0;
    foreach ([1, 2] as $p) {
        $base = GetBase($p);
        if (count($base) > 0 && intval($base[0]->Damage ?? 0) >= 15) $count++;
    }
    if ($count > 0) DoDrawCard(intval($player), $count);
};
