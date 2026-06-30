# JTL_037 Banshee — On Attack: You may deal damage to a unit equal to the damage on this unit. Banshee
# (4/5) has 3 damage on it, so on attack it deals 3 to the enemy SOR_046 (3/7 → 3 damage, survives).
# Banshee attacks the base for its power 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_037:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:4
