<?php
// LAW_111
// Cost 2 - Leia's Disguise - [Vigilance,Heroism] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains the Underworld trait. / When Played: If attached unit is Leia Organa, give a Shield token to a friendly unit.

// LAW_111 Leia's Disguise — When Played (as an upgrade): if the attached unit is Leia Organa, give a
// Shield token to a friendly unit. ($mzID = the host unit. The Underworld-trait grant is passive in
// _SWUUnitHasTrait.)
$whenPlayedAbilities["LAW_111:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (CardTitle($host->CardID ?? '') !== 'Leia Organa') return;
    $targets = array_values(SWUAllUnits('my'));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_friendly_unit", "GIVE_SHIELD");
};
