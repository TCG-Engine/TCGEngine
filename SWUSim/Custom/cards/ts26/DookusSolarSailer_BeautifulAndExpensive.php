<?php
// TS26_38
// Cost 3 - Dooku's Solar Sailer - Beautiful and Expensive - [Vigilance,Villainy] - Power 2 - HP 4
// Text: When Played/On Attack: If a base was healed this phase, give an Experience token to another Separatist unit.

// TS26_38 Dooku's Solar Sailer — When Played/On Attack: if a base was healed this phase, give an
// Experience token to another Separatist unit.
$whenPlayedAbilities["TS26_38:0"] = $onAttackAbilities["TS26_38:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(1, 'SWU_BASE_HEALED_PHASE') <= 0) return;
    GiveTokenUpgrade($player, $mzID, [
        'traits' => 'Separatist', 'may' => true, 'excludeSelf' => true,
        'question' => "Give_an_Experience_to_another_Separatist_unit?",
        'prompt'   => "Choose_a_Separatist_unit",
    ]);
};
