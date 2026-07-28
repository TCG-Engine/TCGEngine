<?php
// ASH_108
// Cost 3 - Crix Madine - Strike Team Strategist - [Command,Heroism] - Power 3 - HP 2
// Text: When Played: You may play a Heroism unit from your hand. It costs 2 resources less for each arena in which you control the most units.

// ASH_108 Crix Madine — When Played: you may play a Heroism unit from your hand. It costs 2 resources
// less for each arena in which you control the most units.
$whenPlayedAbilities["ASH_108:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $arenas = 0;
    // Ground
    $myG = 0; foreach (GetGroundArena(intval($player)) as $u) { if (empty($u->removed)) $myG++; }
    $opG = 0; foreach (GetGroundArena($opp) as $u) { if (empty($u->removed)) $opG++; }
    if ($myG > $opG) $arenas++;
    // Space
    $myS = 0; foreach (GetSpaceArena(intval($player)) as $u) { if (empty($u->removed)) $myS++; }
    $opS = 0; foreach (GetSpaceArena($opp) as $u) { if (empty($u->removed)) $opS++; }
    if ($myS > $opS) $arenas++;
    $discount = 2 * $arenas;
    // Heroism units in hand.
    $tg = [];
    foreach (ZoneSearch("myHand", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && strpos(CardAspect($o->CardID ?? '') ?? '', 'Heroism') !== false) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Play_a_Heroism_unit_from_your_hand?", "Choose_a_Heroism_unit", "DISCOUNT_PLAY_FROM_HAND|{$discount}");
};
