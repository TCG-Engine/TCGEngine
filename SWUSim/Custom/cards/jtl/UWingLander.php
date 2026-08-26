<?php
// JTL_070
// Cost 5 - U-Wing Lander - [Vigilance] - Power 2 - HP 2
// Text: When Played: Give 3 Experience tokens to this unit. / When this unit completes an attack (and survives): You may attach an upgrade on this unit to another eligible friendly Vehicle unit.

// ── JTL_070 U-Wing Lander — When Played: Give 3 Experience tokens to this unit. ──────────────────────
$whenPlayedAbilities["JTL_070:0"] = function($player, $mzID) {
    for ($i = 0; $i < 3; $i++) DoGiveExperienceToken(intval($player), $mzID);
};

// JTL_070 — "When this unit completes an attack (and survives): You may attach an upgrade on this unit
// to another eligible friendly Vehicle unit." The "(and survives)" gate is CollectAfterAttackTriggers'
// surviving-attacker null-check. Reuses the move-upgrade subsystem scoped to this host as the source
// and friendly Vehicles as the destination. Skip entirely if no other friendly Vehicle can receive it.
$onAttackEndAbilities["JTL_070:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $selfUid = intval($self->UniqueID ?? 0);
    $hasDest = false;
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($u->CardID ?? '', 'Vehicle')) { $hasDest = true; break; }
    }
    if (!$hasDest) return;
    SWUQueueMoveUpgrade(intval($player), 'nonpilot',
        "Attach_an_upgrade_on_this_unit_to_another_friendly_Vehicle", $mzID, 'friendlyVehicle');
};
