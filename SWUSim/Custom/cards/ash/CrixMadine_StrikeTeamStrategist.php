<?php
// ASH_108
// Cost 3 - Crix Madine - Strike Team Strategist - [Command,Heroism] - Power 3 - HP 2
// Text: When Played: You may play a Heroism unit from your hand. It costs 2 resources less for each arena in which you control the most units.

// ASH_108 Crix Madine — When Played: you may play a Heroism unit from your hand. It costs 2 resources
// less for each arena in which you control the most units.
$whenPlayedAbilities["ASH_108:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "each arena in which you control THE MOST units" is a comparison against EVERY other player, not
    // against one opponent. This compared only against OtherPlayer($player), so above two seats a seat-4
    // board with more units than yours was ignored and the discount was over-granted.
    // "The most" = strictly more than every other player; a tie is not "the most", which is the reading
    // the two-seat code already used ($myG > $opG).
    // ⚠ Every OTHER player, not every opponent: in a team game a teammate still controls units, and the
    // card asks who controls the most — teams do not pool boards for that comparison.
    $arenas = 0;
    $countIn = function(callable $zone, int $seat): int {
        $n = 0; foreach ($zone($seat) as $u) { if (empty($u->removed)) $n++; } return $n;
    };
    foreach ([fn($s) => GetGroundArena($s), fn($s) => GetSpaceArena($s)] as $zone) {
        $mine = $countIn($zone, intval($player));
        $most = true;
        foreach (GetLiveSeatsArray() as $seat) {
            if ($seat === intval($player)) continue;
            if ($countIn($zone, $seat) >= $mine) { $most = false; break; }
        }
        if ($most) $arenas++;
    }
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
