<?php
// TS26_34
// Cost 6 - Fives - I Have Proof! - [Cunning,Vigilance,Heroism] - Power 6 - HP 6
// Text: Sentinel / You may have this unit enter play with the "When Played" abilities of another unit in play.

// TS26_34 Fives — Sentinel. When Played: you may have this unit enter play with the "When Played"
// abilities of another unit in play (re-resolve the chosen unit's When Played, with Fives as the source).
$whenPlayedAbilities["TS26_34:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;         // another unit
        if (!HasWhenPlayedAbility($o->CardID ?? '')) continue;          // must have a When Played to copy
        $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Copy_another_unit's_When_Played?", "Choose_a_unit_to_copy", "TS26_34#0|" . $selfUID);
};

$customDQHandlers["TS26_34#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    $chosenCID = $chosen->CardID ?? '';
    $fivesMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($fivesMz === null) return;
    OnWhenPlayed(intval($player), $chosenCID, $fivesMz);   // resolve the copied When Played as Fives
};
