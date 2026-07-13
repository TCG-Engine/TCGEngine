# TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — three enemies pinged; P1 declines the first TWO offers
# and accepts the THIRD. Only the third enemy is exhausted, proving the single leader-exhaust can be spent
# on any one of the pings (declines don't consume it).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
- P1>AnswerDecision:-
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:DAMAGE:1
P2GROUNDARENAUNIT:2:EXHAUSTED
P2GROUNDARENACOUNT:3
P1LEADER:EXHAUSTED
