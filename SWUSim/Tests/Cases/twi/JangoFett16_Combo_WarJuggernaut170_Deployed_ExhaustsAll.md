# TWI_016 Jango Fett (DEPLOYED) + JTL_170 War Juggernaut — "When Played: deal 1 to each of any number of
# units" is friendly-unit AoE damage, so each enemy it hits pings the deployed Jango. P1 damages two
# enemies and exhausts BOTH (deployed side, no leader cost).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1:1;myResources:6;handCardIds:JTL_170}
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
