<?php
// HMW_210
// Cost 2 - Sol - Compassionate Guardian - [Cunning,Heroism] - Unit (Ground) 2/2 - Traits: Force, Jedi
// Text: Shielded (When you play this unit, give a Shield token to it.)
//       On Attack: This unit gains Sentinel for this phase.

// HMW_210 Sol — On Attack: this unit gains Sentinel for this phase.
//
// The clause is ASH_099 Gozanti Assault Carrier's sentence word for word, so this is its one-liner:
// the shared 'SENTINEL' registry row (GRANT_KEYWORD) tagged with the SOURCE CardID, which is what puts
// Sol's own art and a "Phase" chip on the Active Effects badge. SWU_DUR_PHASE is the registry default,
// stated explicitly here because the duration is the whole point of the clause — SWUExpireTurnEffects
// drops it at RegroupPhaseStart, and Tests/.../Sol_CompassionateGuardian.md::SentinelExpiresAtEndOfPhase
// is what proves it (a permanent grant passes every other section in that file).
//
// Shielded needs no wiring — the keyword generator derives it from the card text.
$onAttackAbilities["HMW_210:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    AddTurnEffect($mzID, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'HMW_210'));
};
