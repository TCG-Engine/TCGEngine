<?php
// SEC_111
// Cost 2 - Jar Jar Binks - Mesa Propose... - [Command] - Power 2 - HP 1
// Text: When Played: You may give another friendly unit +2/+2 for this phase. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_111 Jar Jar Binks — When Played: you may give another friendly unit +2/+2 for this phase.
$whenPlayedAbilities["SEC_111:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $selfUID = SWUObjUID($self, 0);
    $others = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueMayChooseTarget(intval($player), $others, "Give_another_friendly_unit_+2/+2?", "Choose_a_unit", "APPLY_PHASE_BUFF|2|2|");
};
