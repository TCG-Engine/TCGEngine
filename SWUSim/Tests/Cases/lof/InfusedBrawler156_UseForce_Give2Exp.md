# LOF_156 Infused Brawler (2/2) — When Played: may use the Force → give 2 Experience tokens to this unit.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_156}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
