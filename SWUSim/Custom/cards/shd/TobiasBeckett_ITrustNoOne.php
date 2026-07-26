<?php
// SHD_217
// Cost 4 - Tobias Beckett - I Trust No One - [Cunning] - Power 4 - HP 5
// Text: When you play a non-unit card: You may exhaust a unit that costs the same as or less than the card you played. Use this ability only once each round. / Smuggle [5 resources Vigilance]

// ─── SHD_217 Tobias Beckett (reactive: play non-unit → may exhaust a ≤cost unit, once/round) ───
$customDQHandlers["SHD_217#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $o->Status = 0;   // exhaust the chosen unit
    AddGlobalEffects(intval($player), 'SWU_SHD217_USED');   // once each round — consumed on actual use
};
