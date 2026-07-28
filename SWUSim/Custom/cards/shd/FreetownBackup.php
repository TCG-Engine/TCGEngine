<?php
// SHD_097
// Cost 2 - Freetown Backup - [Command,Heroism] - Power 1 - HP 4
// Text: On Attack: Give another friendly unit +2/+2 for this phase. / Smuggle [4 resources Command Heroism] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_097 Freetown Backup ───────────────────────────────────────────────────
// On Attack: Give ANOTHER friendly unit +2/+2 for this phase. MZMAYCHOOSE (the OnAttack-safe
// choose — a mandatory MZCHOOSE queued in the OnAttack closure is silently skipped); registry row
// 'SHD_097' STAT_BUFF gives provenance + central phase expiry.
$onAttackAbilities["SHD_097:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_another_friendly_unit_+2/+2_this_phase", "Give_another_friendly_unit_+2/+2_this_phase",
        "APPLY_PHASE_BUFF|2|2|SHD_097");
};
