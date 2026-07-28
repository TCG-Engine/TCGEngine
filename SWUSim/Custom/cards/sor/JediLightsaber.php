<?php
// SOR_054
// Cost 3 - Jedi Lightsaber - [Vigilance,Heroism] - Upgrade Power 3 - Upgrade HP 3
// Text: Attach to a non-VEHICLE unit. / If attached unit is a FORCE unit, it gains: "On Attack: Give the defender -2/-2 for this phase."

$onAttackAbilities["SOR_054:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    if (!TraitContains($host, 'Force')) return;
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    // Only units have stats — a base attack has no unit defender to shrink.
    if (strpos($defenderMz, 'Arena') === false) return;
    $defender = GetZoneObject($defenderMz);
    if ($defender === null || ($defender->removed ?? false)) return;
    SWUApplyPhaseDebuff($defenderMz, 2, 2, 'SOR_054');
};
