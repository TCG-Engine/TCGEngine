# BuffGroundAndSpace
#// JTL_253 Coordinated Front (event) — You may give a ground unit +2/+2 and a space unit +2/+2 this
#// phase. P1 buffs SOR_095 (ground, 3/3 → 5/5) and SOR_237 (space, 2/3 → 4/5).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
