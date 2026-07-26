<?php
// ASH_097
// Cost 3 - Moff Gideon - Remnant Commander - [Command,Villainy] - Power 2 - HP 5
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / When Defeated: You may return a non-<uq> Imperial unit from your discard pile to your hand.

// ASH_097 Moff Gideon — Sentinel (keyword) + When Defeated: you may return a non-unique Imperial unit
// from your discard pile to your hand.
$whenDefeatedAbilities["ASH_097:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (ZoneSearch("myDiscard", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Imperial') && !CardUnique($o->CardID ?? '')) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Return_a_non-unique_Imperial_unit_from_discard?", "Choose_a_unit", "RETURN_DISCARD_UNIT");
};
