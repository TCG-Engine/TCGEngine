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
    // "Take control of attached unit if it's a NON-LEADER unit that costs 3 or less." The attach
    // itself is unrestricted, but the control-take must NEVER run on a leader unit (a piloted
    // leader's printed HOST cost can be <=3; taking control would trip the CR 3.4.6 leader-unit
    // control-change replacement and DEFEAT it — the take should simply not be attempted).
    if (!IsLeaderUnit($host) && intval(CardCost($host->CardID) ?? 99) <= 3) {
        SWUTakeControlOfUnit(intval($player), $mzID);
    }
};
