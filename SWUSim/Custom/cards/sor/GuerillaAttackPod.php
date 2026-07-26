<?php
// SOR_148
// Cost 6 - Guerilla Attack Pod - [Aggression,Heroism] - Power 4 - HP 6
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: If a base has 15 or more damage on it, ready this unit.

// SOR_148 Guerilla Attack Pod — "When Played: If a base has 15 or more damage on it, ready this unit."
$whenPlayedAbilities["SOR_148:0"] = function($player, $mzID) {
    $triggered = false;
    foreach ([1, 2] as $p) {
        foreach (GetBase($p) as $b) {
            if (!empty($b->removed)) continue;
            if (intval($b->Damage) >= 15) { $triggered = true; break 2; }
        }
    }
    if ($triggered) OnReadyCard($player, $mzID);
};
