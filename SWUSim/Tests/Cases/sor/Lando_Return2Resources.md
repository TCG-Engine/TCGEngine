# SOR_197 Lando Calrissian — "When Played: Return up to 2 friendly resources to their owners'
# hands." P1 plays Lando (cost 6) with 8 resources, then returns 2 of them to hand: resources
# 8 → 6, hand gains 2 (started with Lando, played him, +2 returned).

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:6
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
