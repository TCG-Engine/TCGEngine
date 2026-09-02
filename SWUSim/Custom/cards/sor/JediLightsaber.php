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
    // EVERY defender, not just one. Under TWI_135 Darth Maul's two-defender attack both units are
    // defenders of the one attack (official ruling 10/31/2024) and the trigger still fires exactly once,
    // so this ONE firing shrinks both. SWUCurrentDefenderMzIDs() returns a single mzID on every ordinary
    // attack — byte-identical there — and an EMPTY list for a base attack, which is the old 'Arena'
    // guard. Before this it read SWU_CURRENT_DEFENDER, which Maul's path never published at all.
    foreach (SWUCurrentDefenderMzIDs() as $defenderMz) {
        $defender = GetZoneObject($defenderMz);
        if (SWUObjGone($defender)) continue;
        SWUApplyPhaseDebuff($defenderMz, 2, 2, 'SOR_054');
    }
};
