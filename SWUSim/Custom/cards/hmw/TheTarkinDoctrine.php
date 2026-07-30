<?php
// HMW_206
// Cost 1 - The Tarkin Doctrine, Protect and Punish - [Cunning][Villainy] - Upgrade - Trait: Law - Unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "When you play a Fortification upgrade: Exhaust an enemy unit."
//       When Played: If you control Grand Moff Tarkin, give an enemy unit -3/-0 for this phase.
//
// - Fortify attaches it to the base (keyword; SWUGetUpgradeValidTargets returns myBase-0).
// - The granted base ability is a targeted extension of the own-play-upgrade reaction path: the
//   AddTrigger('HMW_206') that arms "exhaust an enemy unit" lives in CollectWhenPlayedAsUpgradeTriggers
//   (GameLogic.php), gated on a Fortification-TRAIT upgrade being played while HMW_206 is on the base. It
//   is dispatched by the DispatchTrigger 'HMW_206' case. HMW_206's own trait is 'Law' (not 'Fortification'),
//   so playing The Tarkin Doctrine itself does NOT trigger its own grant.
// - The When Played half is an ordinary non-pilot-upgrade WhenPlayed ($mzID = the base host).
$whenPlayedAbilities["HMW_206:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Grand Moff Tarkin'])) return;   // gate: control HMW_004 (leader or unit)
    // "give an enemy unit -3/-0 for this phase" — mandatory (auto-resolves a lone enemy; fizzles with none).
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|3|0|HMW_206',
        'side'         => 'their',
        'prompt'       => 'Give_an_enemy_unit_-3/-0_this_phase',
    ]);
};
