<?php
// IC27_019
// Cost 4 - Soulless One - Soulless Fun - [Vigilance][Villainy] - Unit (Space) 3/5
// Traits: Separatist, Vehicle, Fighter - Unique
// Text: When Played: Choose an enemy ground unit. While this unit is in play, the chosen unit gets -3/-0.
//
// ⚠ PREVIEW SET: IC27 is not in card-specific-rulings.md. Modelled on TWI_110 Huyang (same printed shape, friendly
// +2/+2), whose official rulings apply by analogy: the effect lasts until the SOURCE LEAVES PLAY and is not changed by
// a control change; a captured-and-rescued source does not resume it (a rescued unit is a new object, new UniqueID).
// Blanking the source does not end it either (CR 14.3 — the When Played already resolved; see _SWULof191BuffCount).
//
// The link is a PERM turn effect on the CHOSEN unit — "IC27_019-{sourceUID}" — not a global effect keyed to one seat:
// the target is an ENEMY and either unit may change sides, so a per-seat key (Huyang's) can miss it. The mark rides the
// target through a take-control (SWUTakeControlOfUnit keeps UniqueID and TurnEffects) and dies with it when it leaves
// play; the reader counts marks whose source UID is still in play on ANY seat. Keyed by the source's UID, so a copied
// When Played (TS26_34 Fives) ends when the copy leaves, and a repeated one is a second effect that stacks.

$whenPlayedAbilities["IC27_019:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID));
    $targets = [];
    // "ENEMY" — team-relative ('their' excludes a Team Suns teammate; spans every opponent in Twin Suns).
    foreach (SWUAllUnits('their', GroundArena) as $mz) {
        if (!SWUObjGone(GetZoneObject($mz))) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_an_enemy_ground_unit_(it_gets_-3/-0_while_Soulless_One_is_in_play)", "IC27_019#0|{$selfUID}");
};

$customDQHandlers["IC27_019#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('IC27_019', [intval($parts[0] ?? -1)]));
};

// How many "-3/-0 while Soulless One is in play" effects apply to $obj (each live source counts once per mark).
function _SWUIc27019DebuffCount($obj): int {
    if ($obj === null || empty($obj->TurnEffects)) return 0;
    $n = 0;
    foreach ($obj->TurnEffects as $te) {
        if (strncmp((string)$te, 'IC27_019-', 9) !== 0) continue;
        $src = intval(SWUParseTurnEffect((string)$te)['params'][0] ?? 0);
        if (_SWUUnitUIDInPlay($src)) $n++;
    }
    return $n;
}
