<?php
// HMW_231
// Cost 6 - Dragonboat Freighter - [Cunning] - Unit, Space - Power 5 - HP 5
// Traits: Underworld, Vehicle, Transport - non-unique
// Text: When Played: You may give a Weakness token to a unit. If it's a Unique unit, exhaust it.
//
// "YOU MAY" -> MZMAYCHOOSE, so the decline branch is real and must no-op cleanly.
// "A UNIT" is UNQUALIFIED — no friendly/enemy and no arena — so the pool is every unit on the table,
// yours included, plus token units and deployed leader units (AnyUnitFilter via 'side' => 'any').
//
// ⚠ THE RIDER IS CONDITIONAL ON THE TARGET, NOT ON THE GIVING. "If IT'S a Unique unit" reads the card
// that was chosen, so a non-unique target takes the Weakness and stays READY. That asymmetry is the
// whole card, and a handler that exhausted unconditionally passes the obvious positive.
// Uniqueness is the printed flag (CardUnique), not "is there only one of them in play".
//
// ⚠ ORDER: Weakness FIRST, then the exhaust. HMW_T02 is -1/-1, so it can drop a 1-HP unit to 0 remaining
// HP and defeat it via the shrink sweep — after which there is nothing left to exhaust and the rider is
// correctly moot. Re-resolving by UID after the token is what makes that safe.

$whenPlayedAbilities["HMW_231:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'HMW_231#0',
        'side'         => 'any',
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
};

$customDQHandlers["HMW_231#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid      = intval($o->UniqueID ?? 0);
    $isUnique = CardUnique($o->CardID ?? '');
    DoGiveTokenUpgrade(intval($player), $lastDecision, 'HMW_T02');
    // The -1/-1 has no state-based defeat of its own; sweep, then re-find the target — it may be gone.
    SWUCheckShrinkDefeats();
    if (!$isUnique || $uid === 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null && $mz !== '') OnExhaustCard(intval($player), $mz);
};
