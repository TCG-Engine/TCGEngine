<?php
// TWI_034
// Cost 3 - General Grievous - Trophy Collector - [Vigilance,Villainy] - Power 4 - HP 4
// Text: Ignore the aspect penalty on each Lightsaber upgrade you play on this unit. / On Attack: If this unit has 4 or more Lightsaber upgrades attached to him, defeat 4 enemy units.

// TWI_034 General Grievous — On Attack: "If this unit has 4 or more Lightsaber upgrades attached to him,
// defeat 4 enemy units." Count the Lightsaber-trait upgrades on Grievous; if ≥4, defeat 4 enemy units
// (all of them when ≤4 in play, else the player picks 4). Mass defeat by snapshotted UID (index-shift safe).
$onAttackAbilities["TWI_034:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $sabers = 0;
    foreach (GetUpgradesOnUnit($self) as $u) {
        $uid = is_array($u) ? ($u['CardID'] ?? '') : ($u->CardID ?? '');
        if ($uid !== '' && HasTrait($uid, 'Lightsaber')) $sabers++;
    }
    if ($sabers < 4) return;
    $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($enemies)) return;
    if (count($enemies) <= 4) {
        $uids = [];
        foreach ($enemies as $mz) { $o = GetZoneObject($mz); if ($o !== null) $uids[] = intval($o->UniqueID ?? 0); }
        foreach ($uids as $uid) { $m = SWUFindMzByUID($uid); if ($m !== null && $m !== '') SWUDefeatUnit(intval($player), $m); }
        return;
    }
    // More than 4 enemy units — the player picks which 4 to defeat (MZMULTICHOOSE works in OnAttack).
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "4|4|" . implode("&", $enemies), 1,
        tooltip:"Defeat_4_enemy_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TWI_034#0", 1);
};

$customDQHandlers["TWI_034#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picks = array_values(array_filter(explode('&', (string)$lastDecision),
        fn($p) => $p !== '' && $p !== '-' && $p !== 'PASS'));
    $uids = [];
    foreach ($picks as $mz) { $o = GetZoneObject($mz); if ($o !== null) $uids[] = intval($o->UniqueID ?? 0); }
    foreach ($uids as $uid) { $m = SWUFindMzByUID($uid); if ($m !== null && $m !== '') SWUDefeatUnit(intval($player), $m); }
};
