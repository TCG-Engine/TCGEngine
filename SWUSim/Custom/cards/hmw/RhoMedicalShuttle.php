<?php
// HMW_063
// Cost 3 - Rho Medical Shuttle - [Vigilance,Villainy] - Unit (Space) 3/3 - Traits: Imperial, Vehicle, Transport
// Text: When Played/On Attack: You may heal 1 damage from another unit or base.

// ─── HMW_063 Rho Medical Shuttle ───────────────────────────────────────────────────────────────────
// ONE effect on TWO trigger windows, so both registries share the same closure — a card wired to only
// one half passes every assertion about the other (Tests/Cases/hmw/RhoMedicalShuttle.md exercises each
// window separately, and BothWindowsFire_PlayThenAttack proves they are independent).
//
// TARGET SET — "another unit or base" is unqualified on BOTH halves:
//   • units span both sides (no "friendly"), so healing an ENEMY unit is legal;
//   • "or base" likewise covers both bases;
//   • "another" excludes exactly one thing — the Shuttle itself.
// An UNDAMAGED unit stays a legal target: the card says "a unit", not "a damaged unit", so it is chosen
// freely and simply heals nothing (HEAL_TARGET clamps at 0). Filtering zero-effect targets out here
// would be wrong — contrast the cards whose text names a damaged target.
//
// ⚠ MZMAYCHOOSE, not MZCHOOSE, and that matters twice over: the printed "you may" needs a decline, AND
// a mandatory multi-target MZCHOOSE queued from an ON ATTACK closure auto-resolves to nothing and
// silently no-ops (OnAttackTrigger restores $playerID before MZCountChoices runs). The 'may' option
// routes SWUOfferUnitTarget through SWUQueueMayChooseTarget, which is the proven in-combat form.
//
// Neither window closes the action: the When Played rides the play's own FINISH_PLAY_CARD and the On
// Attack is owned by combat, so there is deliberately no SWUAfterAction here.
$whenPlayedAbilities["HMW_063:0"] = $onAttackAbilities["HMW_063:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'HEAL_TARGET',   // universal handler; no-ops on a '-' decline, clamps at 0
        'amount'       => 1,
        'may'          => true,
        'excludeSelf'  => true,            // "another" — by UniqueID off $mzID, the Shuttle's own mz
        'includeBases' => true,
        'baseSide'     => 'any',           // "or base" names no controller either
        'prompt'       => "Heal_1_damage_from_another_unit_or_base",
        'question'     => "Heal_1_damage_from_another_unit_or_base?",
    ]);
};
