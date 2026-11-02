<?php
// IC27_149
// Cost 10 - The Invisible Hand - Confederate Might - [Cunning,Villainy] - Unit (Space) 9/9 (unique)
//   Traits: Separatist, Vehicle, Capital Ship
// Text: When Played/When Defeated: The next card you play this phase costs [9 resources] less.
//
// Both windows arm one charge for the resolving player (the controller — under JTL_043 No Glory, Only Results
// that is the thief). The discount is a $oneShotPlayCharges row (GameLogic, beside IC27_145 Poggle): "the next
// CARD" is any type — unit, event, upgrade, a pilot — and the charge is spent on that card even when it costs
// less than 9. Two charges armed before the next play (When Played, then defeated the same phase) stack on that
// one card and are both spent. Cleared at the regroup.
// Armed only in the action phase: a When Defeated can resolve in the REGROUP phase (SOR_219 Sneak Attack defeats
// its unit there, after RegroupPhaseStart's clears have run), where no card can be played — "this phase" ends
// unused, and arming then would carry the charge into the next action phase.
$whenPlayedAbilities["IC27_149:0"] = $whenDefeatedAbilities["IC27_149:0"] = function($player, $mzID) {
    if (GetCurrentPhase() !== 'MAIN') return;
    AddGlobalEffects(intval($player), 'SWU_IC27149_DISCOUNT_NEXT');
};
