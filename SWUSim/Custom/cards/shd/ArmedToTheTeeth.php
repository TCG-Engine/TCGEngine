<?php
// SHD_175
// Cost 2 - Armed to the Teeth - [Aggression] - Upgrade Power 2 - Upgrade HP 0
// Text: Attached unit gains: "On Attack: Give another friendly unit +2/+0 for this phase." / Smuggle [4 resources Aggression] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_175 Armed to the Teeth ───────────────────────────────────────────────
// Attached unit gains: "On Attack: Give another friendly unit +2/+0 for this phase." Fires via the
// host-upgrade scan in CollectCombatStep1Triggers; MZMAYCHOOSE is the OnAttack-safe choose. "Another"
// excludes the attacking HOST. Registry row 'SHD_175' STAT_BUFF (GameLogic.php).
$onAttackAbilities["SHD_175:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $hostUID = ($host !== null) ? intval($host->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $hostUID) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_another_friendly_unit_+2/+0_this_phase", "Give_another_friendly_unit_+2/+0_this_phase",
        "APPLY_PHASE_BUFF|2|0|SHD_175");
};
