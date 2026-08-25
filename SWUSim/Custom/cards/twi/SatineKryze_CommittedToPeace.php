<?php
// TWI_047
// Cost 4 - Satine Kryze - Committed to Peace - [Vigilance,Heroism] - Power 0 - HP 6
// Text: Each unit (including enemy units) gains: "Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, rounded up."

// TWI_047 Satine Kryze — "Each unit (including enemy units) gains: Action [Exhaust]: Discard cards from
// an opponent's deck equal to half this unit's remaining HP, rounded up." The grant is surfaced on every
// unit by SWUGetUnitActionProvider's _SWUSatineInPlay() fallback; each unit's controller uses it, milling
// THEIR opponent's deck by ceil(remainingHP/2). 'exhaust' cost kind (requires ready, exhausts the unit).
$unitActionCostKind["TWI_047"] = 'exhaust';

// ⚠⚠ THE CLASSIFIER TRAP. Satine's GRANT half is already seat-aware (_SWUSatineInPlay in GameLogic.php),
// so the sweep's "a file that uses ANY seat-aware helper has been CONSIDERED" rule read this card as
// clean — while the MILL half here still asked one seat. The two halves live in DIFFERENT FILES, and one
// converted half whitewashes the other. Apply that rule PER CLAUSE, never per file.
//
// ⚠⚠ AND THE ACTION IS SYNCHRONOUS. It used to end in SWUAfterAction() inline; inserting an interactive
// picker without switching to SWUQueueAfterAction() would CLOSE THE ACTION BEFORE THE MILL RESOLVES.
// The after-action must now trail the picker in the queue, not run ahead of it.
$unitAbilities["TWI_047"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if ($obj === null || !empty($obj->removed)) { SWUAfterAction(intval($player)); return; }
    $remHP  = max(0, intval(ObjectCurrentHP($obj)) - intval($obj->Damage ?? 0));
    $amount = intval(ceil($remHP / 2));
    // ⚠ FREEZE THE AMOUNT IN THE PARAM. It is read off the unit's CURRENT HP, and the unit can be damaged
    // or defeated while the opponent picker is open — re-reading it in the continuation would silently
    // change the mill size (or crash on a dead unit).
    if ($amount <= 0) { SWUAfterAction(intval($player)); return; }
    // "an opponent's deck" — the ACTIVATING unit's controller chooses. ⚠ FILTER to opponents with a
    // non-empty deck: milling an empty deck does nothing at all, so those seats are a choice among
    // nothing. Gate OUTSIDE the picker (it queues nothing at zero eligible, which would strand the
    // after-action).
    $eligible = [];
    foreach (OpponentsOf(intval($player)) as $o) {
        foreach (GetDeck($o) as $d) { if (empty($d->removed)) { $eligible[] = $o; break; } }
    }
    if (empty($eligible)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseOpponent(intval($player), "TWI_047#0|{$amount}",
        "Choose_an_opponent_to_discard_from_their_deck", $eligible);
    SWUQueueAfterAction(intval($player));
};

$customDQHandlers["TWI_047#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = intval($parts[0] ?? 0);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($amount <= 0 || $opp <= 0 || $opp === intval($player)) return;
    for ($i = 0; $i < $amount; $i++) SWUMillTopCard($opp);
};
