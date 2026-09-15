<?php
// HMW_156
// Cost 5 - Arena Acklay - Screaming Predator - [Aggression,Villainy] - Unit (Ground) 5/6
// Traits: Creature - Unique
// Text: When this unit is dealt damage and survives: Deal 2 damage to each enemy base.
//
// HMW_169 Crosshair's clause 1 is the exact shape ("When this unit is dealt damage and survives: …"): a
// self observer hooked BELOW _SWUOnUnitDamaged's $survived gate — that gate IS the "and survives" half —
// queued as a CUSTOM for the controller rather than run inline, because it fires mid-combat and belongs
// after the combat cleanup. "Dealt damage", not "combat damage", so every funnel counts: combat both ways,
// ability damage (HMW_012 Poggle's ping is the deck it was previewed for), divided and indirect damage.
//
// ★ JUDGE RULING 2026-09-14: damage PREVENTED (a Shield, a prevention effect) or REDUCED TO 0 was never
// dealt, so this does not trigger (CR 8.9 / 20.1). _SWUOnUnitDamaged returns on amount <= 0 before any
// observer runs; the $amount check below is belt and braces for a direct caller.
//
// "EACH ENEMY BASE" is a loop over OpponentsOf(the CONTROLLER): every opponent in Twin Suns, never a
// teammate in Team Suns, and after a control change it is the THIEF's enemies (the owner's base included).
// One trigger per damage INSTANCE — two separate hits that it survives are two triggers.

function _SWUHmw156CheckObserve($obj, int $amount): void
{
    if ($obj === null || $amount <= 0)
        return;
    if (($obj->CardID ?? '') !== 'HMW_156')
        return;
    if (LostAbilities($obj))
        return;                              // the reaction is Acklay's own ability
    $ctrl = intval($obj->Controller ?? 0);   // the CONTROLLER's enemies, not the owner's
    if ($ctrl <= 0)
        return;
    DecisionQueueController::AddDecision($ctrl, "CUSTOM", "HMW_156#0", 1);
}

$customDQHandlers["HMW_156#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $ctrl = intval($player);
    if ($ctrl <= 0) return;
    foreach (OpponentsOf($ctrl) as $opp) {
        $playerID = $ctrl;
        SWUDealDamageToBase(2, intval($opp), $ctrl);
    }
    $playerID = $ctrl;
};
