<?php
// SOR_008
// Cost 6 - Hera Syndulla - Spectre Two - [Command,Heroism] - Power 4 - HP 6
// Text: Ignore the aspect penalty on SPECTRE cards you play.
// DeployText: Ignore the aspect penalty on SPECTRE cards you play. / On Attack: You may give an Experience token to another unique unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SOR_008 Hera Syndulla (deployed Leader Unit) — "On Attack: You may give an Experience token to
// another unique unit." (Her aspect-penalty-ignore passive lives in SWUAspectPenalty.) "Another" =
// exclude herself by UID; "unique unit" = any unit (friendly or enemy) with the unique flag.
$onAttackAbilities["SOR_008:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    $uid  = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = _SWUCollectUnits($uid, fn($o) => CardUnique($o->CardID));
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_to_another_unique_unit", "Choose_another_unique_unit", "GIVE_EXPERIENCE|1");
};
