<?php
// ASH_085
// Cost 1 - Grav Charge - [Vigilance] - Upgrade Power 0 - Upgrade HP 0
// Text: When attached unit's attack ends: Deal 4 damage to it and defeat this upgrade.

// ASH_085 Grav Charge (upgrade/Condition) — "When attached unit's attack ends: deal 4 damage to it and
// defeat this upgrade." Fires via the OnAttackEndFromUpgrade scan; $hostMzID = the attacker (host).
$onAttackEndFromUpgradeAbilities["ASH_085"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    SWUDealDamageToUnit($hostMzID, 4, intval($player));
    // Defeat the Grav Charge upgrade itself (it may already be gone if the host was defeated above).
    $h2 = GetZoneObject($hostMzID);
    if ($h2 !== null && empty($h2->removed)) _SWUDefeatNamedUpgrade($h2, 'ASH_085');
};
