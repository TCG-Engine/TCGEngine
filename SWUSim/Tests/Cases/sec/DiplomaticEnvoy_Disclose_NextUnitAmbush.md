# SEC_109 Diplomatic Envoy (Space, 2/2, Command) — When Played: you may disclose Command → the next
#   unit you play this phase gains Ambush for this phase.
# Play SEC_109 → disclose SEC_080 (Command) → arm the "next unit gains Ambush" charge. Then play
# SOR_095 → it enters with Ambush (HASKEYWORD:Ambush, the granted phase keyword).

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_109
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1SPACEARENAUNIT:0:CARDID:SEC_109
