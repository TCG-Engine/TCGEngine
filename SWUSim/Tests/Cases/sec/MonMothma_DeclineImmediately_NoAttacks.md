# SEC_103 Mon Mothma — the multi-attack is optional ("you may"). P1 plays Mon Mothma and declines the
#   first offer → no attacks happen, the enemy unit is untouched, and the play finalizes cleanly.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2
