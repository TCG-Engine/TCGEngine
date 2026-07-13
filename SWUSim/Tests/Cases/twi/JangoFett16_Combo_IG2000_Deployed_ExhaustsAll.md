# TWI_016 Jango Fett (DEPLOYED) + JTL_140 IG-2000 — the deployed side has NO leader-exhaust cost, so it
# can exhaust EVERY enemy the AoE damages. P1 plays IG-2000, deals 1 to two enemies, and exhausts BOTH.
# (Contrast the front-side tests, which cap at one per turn.)
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1:1;myResources:4;handCardIds:JTL_140}
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
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
