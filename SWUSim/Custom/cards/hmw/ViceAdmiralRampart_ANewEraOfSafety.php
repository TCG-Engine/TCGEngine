<?php
// HMW_060
// Cost 2 - Vice Admiral Rampart, A New Era of Safety - [Vigilance][Villainy] - Unit (Ground) 1/5
//   Traits: Imperial, Official - Unique
// Text: If an upgrade on your base would be defeated, you may defeat this unit instead.
//
// A REPLACEMENT effect — it has no registered ability closure. The logic lives at the single chokepoint
// SWUDefeatUpgrade (CombatLogic.php): when a BASE upgrade would be defeated by an ability/effect while its
// controller controls Rampart, the defeat is deferred to action end (the JTL_094 pilot timing the user
// specified — the HMW/Homeworlds CR is not released yet) and the base controller is offered "defeat Rampart
// instead?" via the RAMPART_SAVE continuation (CardDQHandlers.php), flushed by SWUFlushDeferredReplacements.
//
// Ruling (user-confirmed, 2026-07-30; HMW/Homeworlds CR unreleased): replaceable for ANY ability/cost/
// effect defeat of a base upgrade — HMW_081 Alliance Shield Generator (effect), HMW_095 Carbonite Chamber
// (cost), and HMW_171 Trap Field (self-sacrifice). The SWU CR replacement rules support this: a replacement
// effect can replace a COST (the cost still counts as paid via defeating Rampart), and when it replaces the
// text before "If you do" the player still resolves the "If you do" payoff (so HMW_171 still deals its 3).
// Uniqueness enforcement only hosts on arena units, so it never reaches the base branch. Pointer only.
