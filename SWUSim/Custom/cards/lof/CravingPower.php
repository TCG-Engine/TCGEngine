<?php
// LOF_091
// Cost 5 - Craving Power - [Command,Villainy] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a friendly unit. / When Played: Deal damage to an enemy unit equal to attached unit's power.

// LOF_091 Craving Power (+2/+2) — When Played (as an upgrade): deal damage to an enemy unit equal to the
// attached unit's (current, post-attach) power. Non-pilot upgrade → its whenPlayed closure gets the HOST mzID.
$whenPlayedAbilities["LOF_091:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null) return;
    $dmg = intval(ObjectCurrentPower($host));
    if ($dmg <= 0) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $dmg, 'side' => 'their',
        'prompt' => "Deal_{$dmg}_to_an_enemy_unit",
    ]);
};
