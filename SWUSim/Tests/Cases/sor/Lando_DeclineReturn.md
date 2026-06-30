# SOR_197 Lando Calrissian — "up to 2" means the player may return ZERO. Declining the MZMULTICHOOSE
# (min 0) returns nothing: resources stay 8, hand stays empty, Lando is in play.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:8
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
