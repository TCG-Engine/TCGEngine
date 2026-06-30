# SOR_016 Grand Admiral Thrawn — Leader Action: two valid targets → MZCHOOSE → player picks opponent's unit.
# Top of P1 deck = SOR_095 (cost 2). Both P1 and P2 have a SOR_095 (cost 2 <= 2).

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
