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
#// COVERAGE (Phase C update): control=NO LONGER N/A —
#//           StolenRedThree_BuffsItsNewControllersUnits proves the aura is keyed to the CONTROLLER in
#//           both directions at once (P2's Heroism unit gains the +1, P1's loses it) ·
#//           scope=CrossArena_SpaceRedThreeBuffsAGroundAlly (the aura is arena-blind — the one section
#//           an arena-local reading cannot survive) and NoRaidWhileDefending ("while ATTACKING", so a
#//           granted Raid never shows up on the defending side) · persistence=
#//           GrantEndsWhenRedThreeLeavesPlay (the aura is a live board read, not a stamp on the ally:
#//           removing Red Three drops the ally back to its printed 3) · value class=
#//           RaidAppliesAttackingAUnit_NotOnlyTheBase (the +1 rides an attack on a UNIT, not just a base)

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

---

# RaidAppliesAttackingAUnit_NotOnlyTheBase
#// SOR_144 Red Three — Raid is "while attacking", full stop: it is not a base-attack bonus. Every
#// existing section drives the buffed ally into the enemy BASE; here the granted Raid 1 rides an attack
#// on an enemy UNIT instead. P1's Consular Security Force (3/7, Heroism) swings into P2's identical
#// 3/7 for 3+1 = 4 and takes the plain 3 back — the asymmetry IS the grant.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1SpaceArena: SOR_144:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# NoRaidWhileDefending
#// SOR_144 Red Three — the reminder text is "+1/+0 WHILE ATTACKING", so the granted Raid does nothing on
#// the defending side. The same board as RaidAppliesAttackingAUnit_NotOnlyTheBase with the attack
#// declared from P2 instead: P1's buffed Consular Security Force deals back its printed 3, not 4. An
#// implementation that added the +1 to current power outright (rather than to the attack) would put 4
#// on P2's unit here and pass every existing section.

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SOR_144:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0

---

# CrossArena_SpaceRedThreeBuffsAGroundAlly
#// SOR_144 Red Three — "each other friendly [Heroism] unit" names no arena. Red Three is a SPACE card;
#// its aura must still reach a friendly Heroism unit on the GROUND. Consular Security Force attacks
#// P2's base for 3+1 = 4 with Red Three parked in space. (BuffsOtherHeroismRaid seats Red Three in the
#// ground arena beside its beneficiary, so an arena-local reading of the aura would survive it; this is
#// the section that does not.)

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1SpaceArena: SOR_144:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# GrantEndsWhenRedThreeLeavesPlay
#// SOR_144 Red Three — the aura is a live read of the board, not a one-time stamp on the ally. P2
#// removes Red Three with Rival's Fall (SHD_079, "Defeat a unit"); P1's Consular Security Force then
#// attacks the base and deals its printed 3. Pairs with CrossArena_SpaceRedThreeBuffsAGroundAlly, the
#// identical attack with Red Three still on the board, which deals 4.

## GIVEN
CommonSetup: rrw/bbk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SHD_079
WithP1SpaceArena: SOR_144:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:0
P2BASEDMG:3

---

# StolenRedThree_BuffsItsNewControllersUnits
#// SOR_144 Red Three — the CONTROL axis, both directions in one section. Red Three is OWNED by P1 but
#// CONTROLLED by P2 (the end state after a take-control effect). "Each other FRIENDLY Heroism unit" is
#// read from the CONTROLLER, so the aura now serves P2 and has stopped serving P1: P1's own Heroism
#// Consular Security Force attacks for its printed 3, while P2's Heroism Battlefield Marine attacks for
#// 3+1 = 4. An owner-keyed aura would give exactly the opposite pair of numbers.

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArenaControlled: SOR_144:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:4
