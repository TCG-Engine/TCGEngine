# SOR_107 Command (event, cost 4) — "Choose two." PowerStrike (a friendly unit deals its power to a
# non-unique enemy unit): SEC_080 (3 power) deals 3 to LAW_124 (non-unique). Then Experience: give 2
# Experience tokens to SEC_080 (UPGRADECOUNT 2).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1
