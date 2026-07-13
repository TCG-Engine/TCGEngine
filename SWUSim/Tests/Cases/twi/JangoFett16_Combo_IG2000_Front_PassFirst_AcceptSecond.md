# TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — "When Played: deal 1 to each of up to 3 units" damages
# multiple enemies, pinging Jango once per enemy. On the front side the single leader-exhaust can pay for
# only ONE, but the player chooses WHICH: here P1 DECLINES the first ping and ACCEPTS the second, so the
# second enemy is exhausted (Jango's exhaust wasn't spent on the first).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED
