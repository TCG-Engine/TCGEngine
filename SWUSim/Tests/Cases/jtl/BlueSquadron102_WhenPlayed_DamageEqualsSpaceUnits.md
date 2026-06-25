# JTL_102 Resistance Blue Squadron — When Played: You may deal damage to a unit equal to the number of
# friendly space units. With two friendly space units already in play (SOR_237, JTL_069), playing
# JTL_102 makes three, so it deals 3 to the enemy SOR_046 (3/7 → 3 damage, survives). Counting INCLUDES
# the just-entered JTL_102.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: JTL_069:1:0
WithP1Hand: JTL_102
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENACOUNT:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
