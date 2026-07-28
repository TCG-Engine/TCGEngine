<?php
// JTL_222
// Cost 4 - Kimogila Heavy Fighter - [Cunning] - Power 3 - HP 4
// Text: When Played: Deal 3 indirect damage to a player. Exhaust each unit damaged this way. (That player assigns 3 unpreventable damage among their base and units.)

// ── JTL_222 Kimogila Heavy Fighter — When Played: 3 indirect to a player; exhaust each unit damaged this
// way (from the continuation's damaged-UID list).
$whenPlayedAbilities["JTL_222:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectToChosenPlayer(intval($player), 3, "JTL_222#0", _SWUSrcUID($mzID));
};

$customDQHandlers["JTL_222#0"] = function($player, $parts, $lastDecision) {
    global $gLastIndirectUnitUIDs, $playerID;
    if (!is_array($gLastIndirectUnitUIDs)) return;
    $playerID = intval($player);
    foreach ($gLastIndirectUnitUIDs as $uid) {
        $mz = SWUFindMzByUID(intval($uid));
        if ($mz !== null) OnExhaustCard(intval($player), $mz);
    }
};
