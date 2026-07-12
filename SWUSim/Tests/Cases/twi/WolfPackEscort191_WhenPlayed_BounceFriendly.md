# TWI_191 Wolf Pack Escort (Unit 2/1, Space, cost 1, Cunning/Heroism, Republic/Vehicle/Fighter) — "When
# Played: You may return a friendly non-leader, non-Vehicle unit to its owner's hand." Returning the
# friendly SOR_095 puts it back in P1's hand. Base y + leader yw cover both Cunning/Heroism pips.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_191
