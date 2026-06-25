# LAW_256 — "use ANY NUMBER" includes zero: choosing none re-resolves nothing. LAW_055 stays 1/2 with
# no Experience token.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: LAW_055:1:0
WithP1Hand: LAW_256

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
