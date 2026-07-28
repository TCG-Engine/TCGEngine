<?php
// ASH_081
// Cost 5 - Nebulon-C Frigate - [Vigilance] - Power 3 - HP 6
// Text: When Played: You may heal 3 damage from a unit or base.

// ASH_081 Nebulon-C Frigate — When Played: you may heal 3 damage from a unit or base.
$whenPlayedAbilities["ASH_081:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Only offer targets that actually carry damage — a "heal" with nothing to heal shows no prompt.
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $tg[] = $mz;
    }
    foreach (['myBase-0', 'theirBase-0'] as $bmz) {
        $b = GetZoneObject($bmz);
        if ($b !== null && intval($b->Damage ?? 0) > 0) $tg[] = $bmz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Heal_3_from_a_unit_or_base?", "Choose_a_unit_or_base", "HEAL_TARGET|3");
};
