<?php
// LOF_037
// Cost 6 - Darth Vader - Twilight of the Apprentice - [Vigilance,Villainy] - Power 5 - HP 6
// Text: When Played: Give a Shield token to a friendly unit and to an enemy unit. / On Attack: Defeat an enemy unit with a Shield token on it.

// LOF_037 Darth Vader — When Played: Shield a friendly AND an enemy unit. On Attack: defeat an enemy
// unit with a Shield token on it.
$whenPlayedAbilities["LOF_037:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (!empty($friendly)) SWUQueueChooseTarget(intval($player), $friendly, "Give_a_Shield_to_a_friendly_unit", "GIVE_SHIELD");
    $enemy = SWUAllUnits('their');
    if (!empty($enemy)) SWUQueueChooseTarget(intval($player), $enemy, "Give_a_Shield_to_an_enemy_unit", "GIVE_SHIELD");
};

$onAttackAbilities["LOF_037:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUCountShieldSubcards($o) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    // MZMAYCHOOSE (not mandatory MZCHOOSE): a mandatory multi-target MZCHOOSE auto-skips inside OnAttack.
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_an_enemy_unit_with_a_Shield?", "Choose_a_shielded_enemy", "DEFEAT_UNIT");
};
