# SEC_178 Pursue the Lead — the discarded card costs more than 3 → no Spy. P2's only card SEC_191
#   (cost 5) is discarded; no Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178
WithP2Hand: SEC_191

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION
