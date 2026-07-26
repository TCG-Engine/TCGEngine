<?php
// ASH_118
// Cost 2 - 8D8 - Daimyo's Majordomo - [Command] - Power 1 - HP 4
// Text: Hidden / Action [Exhaust]: Deal 1 damage to another friendly unit. If you do, search the top 5 cards of your deck for a unit, reveal it, and draw it.

// ASH_118 8D8 — Action [Exhaust]: deal 1 damage to another friendly unit. If you do, search the top 5
// cards of your deck for a unit, reveal it, and draw it.
$unitAbilities["ASH_118"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    $tg = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz); if ($o && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $tg[] = $mz;
    }
    if (empty($tg)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $tg, "Deal_1_to_another_friendly_unit", "ASH_118#0");
};

$customDQHandlers["ASH_118#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    DoTopDeckSearch(intval($player), 5, fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false, 1);
    SWUQueueAfterAction($player);
};
