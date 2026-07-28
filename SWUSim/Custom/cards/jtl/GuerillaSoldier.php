<?php
// JTL_218
// Cost 3 - Guerilla Soldier - [Cunning] - Power 2 - HP 3
// Text: When Played: Deal 3 indirect damage to a player. If a base is damaged this way, ready this unit. (That player assigns 3 unpreventable damage among their base and units.)

// ── JTL_218 Guerilla Soldier — When Played: 3 indirect to a player; if a base is damaged this way, ready
// this unit (carry the source UID through the continuation).
$whenPlayedAbilities["JTL_218:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealIndirectToChosenPlayer(intval($player), 3, "JTL_218#0~{$uid}", $uid);
};

$customDQHandlers["JTL_218#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectBaseDmg, $playerID;
    if (intval($gLastIndirectBaseDmg) <= 0) return;
    $uid = intval($parts[0] ?? 0);
    $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
