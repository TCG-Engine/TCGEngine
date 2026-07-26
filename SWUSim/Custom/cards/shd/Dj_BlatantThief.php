<?php
// SHD_213
// Cost 3 - DJ - Blatant Thief - [Cunning] - Power 3 - HP 5
// Text: Smuggle [7 resources Cunning Cunning] / When played using Smuggle: Take control of an enemy resource. When this unit leaves play, that resource's owner takes control of it.

// ─── SHD_213 DJ ───────────────────────────────────────────────────────────────
// When played using Smuggle: Take control of an enemy resource. When this unit leaves play, that
// resource's owner takes control of it. The steal is linked to DJ via SWU_SHD213|{djUID}|{cardID}|{owner}
// on DJ's controller; the lazy leave-play sweep (_SWURevertShd213Steals, run from SWUAfterAction next
// to the SEC_192 twin) reverts it on ANY leave-play path.
$whenPlayedUsingSmuggleAbilities["SHD_213:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $dj  = GetZoneObject($mzID);
    $uid = ($dj !== null) ? intval($dj->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (ZoneSearch('theirResources') as $rmz) {
        $o = GetZoneObject($rmz);
        if ($o !== null && empty($o->removed)) $targets[] = $rmz;
    }
    SWUQueueChooseTarget(intval($player), $targets,
        "Take_control_of_an_enemy_resource", "SHD_213#0|{$uid}");
};

$customDQHandlers["SHD_213#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $djUID = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $opp    = OtherPlayer(intval($player));
    $cardID = $o->CardID;
    $status = intval($o->Status ?? 0);
    $owner  = intval($o->Owner ?? 0); if ($owner <= 0) $owner = $opp;   // resource Owner is often 0
    $o->Remove();
    DecisionQueueController::CleanupRemovedCards();
    AddResources(intval($player), $cardID, $status, $owner, intval($player));
    AddGlobalEffects(intval($player), "SWU_SHD213|{$djUID}|{$cardID}|{$owner}");
};
