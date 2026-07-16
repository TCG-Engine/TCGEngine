# AsUpgrade_GiveExp
#// JTL_086 Wingman Victor Three — When played as an upgrade: You may give an Experience token to another
#// unit. Played onto SOR_225, it gives the token to SOR_046 (3 power → 4).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_086
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
