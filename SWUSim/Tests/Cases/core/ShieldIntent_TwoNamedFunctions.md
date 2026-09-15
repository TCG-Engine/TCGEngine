# Saboteur_DefeatedShields_FireTheUpgradeDefeatedObservers
#// SSOT #7 (gamelog-updates, 2026-09-12) — Shield intent is two named functions now: SWUPreventWithShield (the
#// Shield prevents damage — Galen naming "Shield" blanks it) and SWUDefeatShieldToken (an effect DEFEATS a
#// Shield — Galen does not stop that). The old SWUConsumeShieldToken($unit, $forPrevention = true) defaulted
#// to PREVENTION, which is how Rose Tico and The Mandalorian failed to defeat a Galen-named Shield.
#// Converting the callers found a THIRD path that defeats Shields with its own loop — Saboteur's "defeat the
#// defender's Shields" — and it skipped the "a friendly upgrade was defeated" observers every other Shield
#// defeat fires (ASH_039 Baylan Skoll's phase flag, ASH_161 Zeb Orrelios's deal-1). LOF_215 gives P1's SEC_080
#// Saboteur; it attacks P2's shielded SOR_095: the Shield is defeated, and P2's flag is now set.
#// (Fixture from core/GameLog_Keywords.md Saboteur_ShieldsDefeated_HasALine.)

## GIVEN
CommonSetup: yyk/grw
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2GLOBALEFFECT:SWU_FRIENDLY_UPGRADE_DEFEATED
P1NOGLOBALEFFECT:SWU_FRIENDLY_UPGRADE_DEFEATED
LOGCONTAINS:P1's [[SEC_080|Imperial Dark Trooper]] defeated 1 Shield token on P2's [[SOR_095|Battlefield Marine]] (Saboteur)
