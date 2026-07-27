<?php
// TS26_67
// Cost 4 - Ruping Rider - [Aggression] - Power 3 - HP 4
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: If your base has 15 or more damage on it, deal 2 damage to a base.

// TS26_67 Ruping Rider — Grit (auto). When Played: if your base has 15 or more damage on it, deal 2
// damage to a base.
$whenPlayedAbilities["TS26_67:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $b = GetBase(intval($player));
    if (empty($b) || !isset($b[0]) || intval($b[0]->Damage ?? 0) < 15) return;
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_damage_to_a_base"]);
};
