<?php
// SEC_243
// Cost 5 - FN Trooper Corps - [Villainy] - Power 4 - HP 5
// Text: When Played: Give an Experience token to another friendly unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_243 FN Trooper Corps — When Played: give an Experience token to another friendly unit. (Plot auto.)
$whenPlayedAbilities["SEC_243:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $friendly = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Give_an_Experience_token_to_another_friendly_unit", "GIVE_EXPERIENCE|1");
};
