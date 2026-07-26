<?php
// LAW_059
// Cost 3 - Highsinger - Deadly Droid - [Command,Aggression] - Power 4 - HP 2
// Text: When Played: Give an Experience token to another friendly Command unit. / When Defeated: Give an Experience token to a friendly Aggression unit.

// LAW_059 Highsinger — When Played: Experience to another friendly Command unit. When Defeated:
// Experience to a friendly Aggression unit.
$whenPlayedAbilities["LAW_059:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $uid) continue;
        if (strpos((string)(CardAspect($o->CardID ?? '') ?? ''), 'Command') !== false) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_another_friendly_Command_unit", "GIVE_EXPERIENCE|1");
};

$whenDefeatedAbilities["LAW_059:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && strpos((string)(CardAspect($o->CardID ?? '') ?? ''), 'Aggression') !== false) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_friendly_Aggression_unit", "GIVE_EXPERIENCE|1");
};
