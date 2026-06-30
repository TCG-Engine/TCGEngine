# JTL_222 Kimogila Heavy Fighter — When Played: 3 indirect to a player; exhaust each unit damaged this
# way. P1 plays JTL_222 and aims the indirect at P2. P2 assigns all 3 onto its SOR_046 (3/7, survives),
# which is then exhausted by JTL_222 (it took damage this way).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
