<?php
// HMW_251
// Cost 8 - Blockade Ship - [Villainy] - Unit, Space - Power 5 - HP 8
// Traits: Separatist, Vehicle, Capital Ship - non-unique
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
//       Enemy ground units get -1/-0 while attacking.
//
// SENTINEL needs no code — HMW_251 is in $Sentinel_Cards (generator-derived from the printed text).
//
// THE AURA LIVES IN CombatLogic.php, beside the other "while attacking / while defending" attack-power
// adjustments (JTL_054 Gold Leader, SEC_042 Cassian, LAW_108 Lando, JTL_259 Retrofitted Airspeeder).
// It is NOT a TurnEffect and not a stat override in ObjectCurrentPower: "while attacking" is a combat
// window, so the reduction belongs to the damage calculation and lasts exactly as long as the attack.
// See the comment at that hook for the cross-arena / enemy-scoping / stacking reasoning.
//
// This file is intentionally comment-only: the card's two clauses are a registry keyword and a shared
// combat hook, so there is nothing to register here. It exists so a reader grepping HMW_251 lands
// somewhere that explains where the behaviour actually is.
