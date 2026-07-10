# SHD_239 Toro Calican — "When you play another Bounty Hunter unit: You may deal 1 damage to it. If you
# do, ready this unit. Once each round." Toro starts exhausted; playing SHD_138 (a Bounty Hunter unit)
# deals it 1 and readies Toro.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_239:0:0
WithP1Hand: SHD_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:READY
