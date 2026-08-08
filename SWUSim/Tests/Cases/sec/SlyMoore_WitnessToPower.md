# EnemyMinus2VsBase
#// SEC_033 Sly Moore (Ground, 2/6) — When Played: for this phase, each enemy unit gets -2/-0 while
#//   attacking a base. P1 plays Sly Moore (marks the enemy SOR_046); P2's SOR_046 then attacks P1's
#//   base for 3-2 = 1. (Plot auto.)

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_033

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1

---

# FriendlyAttackerNotReduced
#// SEC_033 Sly Moore — the -2/-0 hits only ENEMY units attacking a base. A FRIENDLY unit attacking the
#// enemy base is unaffected. P1 plays Sly, P2 passes, then P1's SOR_046 (3/7) attacks P2's base for the
#// full 3 (not reduced to 1).

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_033

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# EffectPersistsAfterSlyDefeated
#// SEC_033 Sly Moore — the phase-long -2/-0 is a lasting effect that persists even after Sly herself is
#// defeated. P1 plays Sly; P2 Vanquishes her (SOR_078); then P2's SOR_046 (3/7) attacks P1's base and is
#// still reduced to 1. (CR 8.28: an ongoing effect that has begun continues after its source leaves.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
WithActivePlayer: 1
WithP2Resources: 5
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_033
WithP2Hand: SOR_078

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
P1GROUNDARENACOUNT:0

---

# AppliesToEnemyUnitsPlayedAFTERSlyMoore
#// SEC_033 Sly Moore — "each enemy unit gets -2/-0 while attacking a base for this phase" is a
#// continuous phase effect, not a one-time stamp on the units present when she resolves. P2 has NO units
#// when Sly Moore lands and then plays SOR_193 Millennium Falcon (3 power, and it ENTERS PLAY READY so
#// it can attack in the same phase). That fresh unit still attacks P1's base for only 3 - 2 = 1.

## GIVEN
CommonSetup: bbk/yyw/{myResources:4}
WithActivePlayer: 1
WithP2Resources: 6
WithP1Hand: SEC_033
WithP2Hand: SOR_193

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:1

---

# AppliesToAUnitThatBECOMESEnemyAfterSlyMooreResolves
#// SEC_033 Sly Moore — the debuff follows CONTROL, not the board state at resolution. P1's own SOR_095
#// is unaffected while P1 controls it; P2 then plays SOR_122 Traitorous (which steals a non-leader unit
#// costing 3 or less) to take control of it, and the now-enemy unit attacks P1's base for 3 - 2 = 1.

## GIVEN
CommonSetup: bbk/ggw
WithActivePlayer: 1
WithP1Resources: 4
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_033
WithP2Hand: SOR_122

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
