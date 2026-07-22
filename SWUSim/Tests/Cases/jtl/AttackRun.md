# TwoSpaceUnits
#// JTL_261 Attack Run — Attack with 2 space units (one at a time). SOR_237 and SOR_044 each hit the enemy
#// base for 2, totalling 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_261
WithP1Resources: 3
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:4

---

# OneAttackIfOnlyOneUnit
#// JTL_261 Attack Run — initiates only ONE attack when P1 has a single space unit. SOR_237 attacks the P2
#// base for 2 and is exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_261
WithP1Resources: 3
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1SPACEARENAUNIT:0:EXHAUSTED
