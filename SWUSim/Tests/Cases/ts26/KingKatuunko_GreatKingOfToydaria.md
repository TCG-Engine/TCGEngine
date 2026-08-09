# EnemyGainsRestore
#// TS26_16 King Katuunko — the grant includes ENEMY units. P1 plays Katuunko (granting Restore 1 to all
#// units), then P2's SEC_080 attacks P1's base: its granted Restore 1 heals P2's base (damage 3 → 2).
## GIVEN
CommonSetup: bgw/rrk/{myResources:2;theirBaseDamage:3;handCardIds:TS26_16}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:2
P1BASEDMG:3

---

# FriendlyGainsRestore
#// TS26_16 King Katuunko (Unit 2/4, cost 2) — When Played: all units (incl. enemy) gain Restore 1 for
#// this phase. The friendly SEC_080 already in play gains Restore 1: when it attacks the enemy base, P1's
#// base heals 1 (damage 3 → 2) while combat deals 3 to the enemy base.
## GIVEN
CommonSetup: bgw/rrk/{myResources:2;myBaseDamage:3;handCardIds:TS26_16}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:2
P2BASEDMG:3

---

# FriendlySPACEUnitGainsRestore
#// TS26_16 King Katuunko — "ALL units" spans both arenas, not just the ground one he lands in. The
#// friendly TIE Fighter token in space gains Restore 1: attacking P2's base for 1 also heals P1's base
#// from 3 to 2.

## GIVEN
CommonSetup: bgw/rrk/{myResources:2;myBaseDamage:3;handCardIds:TS26_16}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T01:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:1

---

# EnemySPACEUnitGainsRestore
#// TS26_16 King Katuunko — the enemy half of the same reach. P2's TIE Fighter token gains Restore 1 from
#// P1's own Katuunko, so when it attacks P1's base for 1 it heals P2's base from 3 to 2.

## GIVEN
CommonSetup: bgw/rrk/{myResources:2;theirBaseDamage:3;handCardIds:TS26_16}
SkipPreGame: true
WithActivePlayer: 1
WithP2SpaceArena: JTL_T01:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:1

---

# KatuunkoHimselfGainsRestore
#// TS26_16 King Katuunko — "all units" includes the one whose ability it is; he is in play by the time it
#// resolves. A unit is exhausted when it enters play, so Keep Fighting (SOR_169, "ready a unit with 3 or
#// less power" — 4 here for the uncovered Aggression) readies him first; his attack then deals 2 to P2's
#// base and heals P1's from 3 to 2 off his own granted Restore.

## GIVEN
CommonSetup: bgw/rrk/{myResources:6;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_16 SOR_169]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# RestoreGrantLastsOnlyForThatPhase
#// TS26_16 King Katuunko — "for this phase". Katuunko is played, the phase is passed out and the next
#// round's resource step declined; SEC_080's attack then deals its 3 with no heal, leaving P1's base at
#// the 3 damage it started with.

## GIVEN
CommonSetup: bgw/rrk/{myResources:2;myBaseDamage:3;handCardIds:TS26_16}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# RestoreTriggersOnEveryAttack
#// TS26_16 King Katuunko — the grant is Restore, not a one-shot heal: it fires for each granted unit's
#// attack. Two friendly units attack P2's base for 3 each (6 total) and P1's base heals twice, 5 -> 3.

## GIVEN
CommonSetup: bgw/rrk/{myResources:2;myBaseDamage:5;handCardIds:TS26_16}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:6
