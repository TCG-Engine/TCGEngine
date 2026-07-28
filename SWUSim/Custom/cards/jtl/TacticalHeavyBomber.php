<?php
// JTL_152
// Cost 5 - Tactical Heavy Bomber - [Aggression,Heroism] - Power 3 - HP 5
// Text: On Attack: Deal indirect damage equal to this unit's power to the defending player. If a base is damaged this way, draw a card. (That player assigns that much unpreventable damage among their base and units.)

// ── JTL_152 Tactical Heavy Bomber — On Attack: indirect = power to the defending player; if a base is
// damaged this way, draw. (Reactive seam via the indirect "then" continuation.)
$onAttackAbilities["JTL_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    $power = ($o !== null) ? ObjectCurrentPower($o) : 0;
    if ($power <= 0) return;
    SWUDealIndirectDamage(intval($player), $power, OtherPlayer(intval($player)), "JTL_152#0", _SWUSrcUID($mzID));
};

$customDQHandlers["JTL_152#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectBaseDmg;
    if (intval($gLastIndirectBaseDmg) > 0) DoDrawCard(intval($player), 1);
};
