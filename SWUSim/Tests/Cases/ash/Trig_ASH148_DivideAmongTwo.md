# ASH_148 Ninth Sister — the cost damage is divided as you choose among any number of units. P2 discards
# SOR_046 (cost 4); P1 splits it 2/2 across SEC_080 and SEC_135 (both survive with 2 damage each).
## GIVEN
CommonSetup: rrk/rrk/{myResources:7;handCardIds:ASH_148;theirHandCardIds:SOR_046}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
