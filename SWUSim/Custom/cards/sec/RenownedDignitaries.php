<?php
// SEC_102
// Cost 6 - Renowned Dignitaries - [Command,Heroism] - Power 5 - HP 6
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: Heal 2 damage from your base for each friendly Official unit.

// SEC_102 Renowned Dignitaries — When Played: heal 2 damage from your base for each friendly Official
// unit (includes itself — it's an Official).
$whenPlayedAbilities["SEC_102:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Official')) $n++;
    }
    if ($n > 0) OnHealBase(intval($player), intval($player), 2 * $n);
};
