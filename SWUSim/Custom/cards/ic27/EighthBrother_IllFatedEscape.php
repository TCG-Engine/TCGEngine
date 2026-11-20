<?php
// IC27_099
// Cost 2 - Eighth Brother - Ill-Fated Escape - [Aggression,Villainy] - Unit (Ground) 3/1 (unique)
//   Traits: Force, Imperial, Inquisitor
// Text: When Defeated: You may ready an Imperial unit.
//
// SOR_226 Admiral Motti's shape with a TRAIT gate instead of an aspect gate. "an Imperial unit" has no
// "friendly", so the pool is the whole table — enemy units and Twin Suns far seats included — and a
// unit that is already ready is still a legal (if pointless) pick, exactly like Motti's pool.
// Eighth Brother is Imperial himself, but needs no self-exclusion: probed on all four defeat paths
// (combat, a single-target event, take-control-then-defeat, a simultaneous AoE) he has already left the
// arena when this pool is built. Two sections pin that (EffectDefeat_… / SimultaneousAoEDefeat_…) —
// if they ever red, the engine started dispatching When Defeated before cleanup.
$whenDefeatedAbilities["IC27_099:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'READY_UNIT', 'may' => true,
        'traits' => 'Imperial',
        'question' => "Ready_an_Imperial_unit?", 'prompt' => "Choose_an_Imperial_unit_to_ready",
    ]);
};
