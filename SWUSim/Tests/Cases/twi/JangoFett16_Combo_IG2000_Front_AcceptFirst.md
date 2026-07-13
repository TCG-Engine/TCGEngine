# TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — P1 ACCEPTS the first ping (exhaust Jango → exhaust enemy
# 0), then tries to accept the second too. The front side's cost (exhausting the leader) is already spent,
# so the second acceptance can't pay and does nothing — enemy 1 stays ready. Proves the once-per-turn cap.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED
