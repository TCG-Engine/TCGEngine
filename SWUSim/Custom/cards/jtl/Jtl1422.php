<?php
// JTL_1422
// JTL_1422
// Text: 

$customDQHandlers["JTL_1422#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    if (strpos($lastDecision, 'Base') !== false) {
        $dp = (strpos($lastDecision, 'my') === 0) ? intval($player) : OtherPlayer(intval($player));
        SWUDealDamageToBase(1, $dp, intval($player));
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};
