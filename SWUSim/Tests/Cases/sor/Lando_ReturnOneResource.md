# SOR_197 Lando Calrissian — "Return up to 2" with the player choosing to return exactly ONE. Plays
# Lando (cost 6) with 8 resources, returns 1 to hand → resources 8 → 7, hand gains 1.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:7
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
