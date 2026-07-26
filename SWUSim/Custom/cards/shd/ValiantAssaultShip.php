<?php
// SHD_151
// Cost 4 - Valiant Assault Ship - [Heroism,Aggression] - Power 3 - HP 4
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: If the defending player controls more resources than you, this unit gets +2/+0 for this attack.

// ─── SHD_151 Valiant Assault Ship ─────────────────────────────────────────────
// Saboteur (auto) + On Attack: If the defending player controls more resources than you, this unit gets
// +2/+0 for this attack (a one-shot attack-power bonus consumed in SWUCombatDamage).
$onAttackAbilities["SHD_151:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(OtherPlayer(intval($player))) > SWUResourceCount(intval($player))) {
        SWUAddAttackPowerBonus($mzID, 2);
    }
};
