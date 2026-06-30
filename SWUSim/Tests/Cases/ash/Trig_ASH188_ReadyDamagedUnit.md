# ASH_188 Galvanized Leap (Event, cost 4) — Ready a unit that was damaged this phase. P1's SOR_046
# attacks SEC_080 (taking 3 counter damage, becoming exhausted and "damaged this phase"); then Galvanized
# Leap readies it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:ASH_188}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
