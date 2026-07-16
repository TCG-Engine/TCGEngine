# VehicleAttack_PreventSelfDamage
#// JTL_193 I Have You Now — Attack with a Vehicle; prevent all damage that would be dealt to it this
#// attack. SOR_237 attacks SOR_044: the defender takes 2, but SOR_237's counter-damage is prevented (0).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2
