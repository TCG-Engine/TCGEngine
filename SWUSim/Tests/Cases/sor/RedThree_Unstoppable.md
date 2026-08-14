# BuffsOtherHeroismRaid
#// SOR_144 Red Three (2/3) — "Each other friendly [Heroism] unit gains Raid 1."
#// P1 controls Red Three + a Heroism unit (Consular Security Force SOR_046, Heroism,
#// power 3). With Red Three out, SOR_046 has Raid 1: attacking P2's base deals
#// 3 + 1 = 4 damage. (Red Three itself is not attacking; the grant excludes itself.)
#// COVERAGE: offer=N/A (static aura, no decision is ever raised) · reqboundary=all sections (the
#//           aura is recomputed live at attack-damage time, a separate request from setup) ·
#//           control=N/A (no scenario ported; the aura reads its controller's units live, and the
#//           NoRaidForEnemyHeroism section pins the friendly-side scoping) · boundary
#//           pair=BuffsOtherHeroismRaid (+1 exactly, 3→4) vs NoRaidForSelf (Red Three's own attack
#//           is printed power + its own printed Raid 1 only — the aura never adds a second +1) ·
#//           decline=N/A (static aura)

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: SOR_144:1:0    # Red Three (2/3, Aggression/Heroism) — index 0
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7, Heroism) — index 1

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:4

---

# NoRaidForFriendlyNonHeroism
#// "Each other friendly [Heroism] unit gains Raid 1." — aspect scoping: a friendly NON-Heroism
#// unit gets nothing. Imperial Dark Trooper (SEC_080, Command/Villainy, 3/3) attacks the base
#// with Red Three in play: 3 damage, not 4.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1SpaceArena: SOR_144:1:0     # Red Three (Aggression/Heroism) — space, its printed arena
WithP1GroundArena: SEC_080:1:0    # Imperial Dark Trooper (Command/Villainy, 3/3) — NOT Heroism

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:3

---

# NoRaidForSelf
#// "Each OTHER friendly [Heroism] unit" — Red Three never buffs itself. Attacking the base it
#// deals 2 (power) + 1 (its own PRINTED Raid 1) = 3. Were the aura self-applied, the total
#// would be 4 — the 3 pins that the grant excludes its source.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1SpaceArena: SOR_144:1:0     # Red Three (2/3, printed Raid 1) — Heroism itself

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# NoRaidForEnemyHeroism
#// "Each other FRIENDLY [Heroism] unit" — an ENEMY Heroism unit gets nothing from P1's Red
#// Three. P1 passes; P2's Battlefield Marine (Command/Heroism, 3/3) attacks P1's base for
#// exactly its printed 3.

## GIVEN
CommonSetup: rrw/rrw
WithP1SpaceArena: SOR_144:1:0     # P1's Red Three
WithP2GroundArena: SOR_095:1:0    # enemy Battlefield Marine (Command/Heroism, 3/3)

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:0
