# TWI_191 Wolf Pack Escort — the "may" is optional: declining (AnswerDecision:-) leaves the friendly unit
# in play.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
