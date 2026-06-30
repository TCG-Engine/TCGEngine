# JTL_057 Astromech Pilot — When played as an upgrade: You may heal 2 damage from a unit. Played onto
# SOR_225, it heals the damaged SOR_046 (3 → 1 damage).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_057
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
