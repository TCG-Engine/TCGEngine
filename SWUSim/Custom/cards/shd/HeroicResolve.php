<?php
// SHD_155
// Cost 1 - Heroic Resolve - [Heroism,Aggression] - Upgrade Power 1 - Upgrade HP 1
// Text: Attached unit gains: "Action [2 resources, defeat a Heroic Resolve on this unit]: Attack with this unit. It gets +4/+0 and gains Overwhelm for this attack."

// SHD_155 Heroic Resolve (granted) — "Action [2 resources, defeat a Heroic Resolve on this unit]: Attack
// with this unit. It gets +4/+0 and gains Overwhelm for this attack." The 2-resource cost is paid by
// SWUUnitAction ($unitActionResourceCosts); this closure pays the defeat-the-upgrade cost, grants the
// per-attack buff + Overwhelm, then attacks. costKind 'none' (no exhaust) — availability gates on ready.
$unitActionCostKind["SHD_155"]      = 'none';

$unitActionResourceCosts["SHD_155"] = 2;

$unitAbilities["SHD_155"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) { SWUAfterAction($player); return; }
    $ups = GetUpgradesOnUnit($host);
    $idx = -1;
    for ($i = 0; $i < count($ups); $i++) {
        $cid = is_array($ups[$i]) ? ($ups[$i]['CardID'] ?? '') : ($ups[$i]->CardID ?? '');
        if ($cid === 'SHD_155') { $idx = $i; break; }
    }
    if ($idx < 0) { SWUAfterAction($player); return; }
    SWUDefeatUpgrade(intval($player), $mzID, $idx);   // cost: defeat a Heroic Resolve on this unit
    SWUAddAttackPowerBonus($mzID, 4);                 // +4/+0 for this attack
    AddTurnEffect($mzID, 'SHD_155');                  // Overwhelm for this attack (registry, attack-duration)
    BeginSWUAttack(intval($player), $mzID);           // BeginSWUAttack owns the combat continuation / after-action
};
