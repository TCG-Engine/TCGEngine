# LAW_080 Luke Skywalker (9/7) — When Played: an opponent chooses one: [create a Credit token; ready
# this unit] OR [you may deal 5 to a unit]. The opponent picks Deal5 -> P1 deals 5 to the enemy SOR_046.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Deal5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
