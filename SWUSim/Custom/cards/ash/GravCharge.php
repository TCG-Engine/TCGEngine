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
    $uid = intval($host->UniqueID ?? 0);
    SWUDealDamageToUnit($hostMzID, 4, intval($player));
    // Defeat the Grav Charge upgrade itself (it is already gone if the host was defeated above). Re-find the
    // host by UID: after a lethal 4 its old mzID names the next unit, whose OWN Grav Charge must stay.
    $mz = SWUFindMzByUID($uid);
    $h2 = $mz !== null ? GetZoneObject($mz) : null;
    if ($h2 !== null && empty($h2->removed)) _SWUDefeatNamedUpgrade($h2, 'ASH_085');
};
