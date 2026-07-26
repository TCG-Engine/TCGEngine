<?php
// SOR_122
// Cost 5 - Traitorous - [Command] - Upgrade Power 0 - Upgrade HP 0
// Text: When this upgrade becomes attached to a non-leader unit that costs 3 or less: Take control of that unit. / When this upgrade becomes unattached from a unit: That unit's owner takes control of it.

// ── SOR_122 Traitorous — OnAttached ──────────────────────────────────────────
// "When this upgrade becomes attached to a non-leader unit that costs 3 or less:
// Take control of that unit." (The "becomes unattached → return control" half is
// handled at the SWUDefeatUpgrade chokepoint in CombatLogic.php ~248-255.)
$onAttachedAbilities["SOR_122:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    if (intval(CardCost($host->CardID) ?? 99) <= 3) {
        SWUTakeControlOfUnit(intval($player), $mzID);
    }
};
