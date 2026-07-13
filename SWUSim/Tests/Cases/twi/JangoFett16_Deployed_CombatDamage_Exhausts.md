# TWI_016 Jango Fett (DEPLOYED leader unit) — "When a friendly unit deals damage to an enemy unit: You may
# exhaust that unit." (No leader-exhaust cost on the deployed side.) P1 deploys Jango (cost 5) and attacks
# the enemy 3/7 with him: 3 combat damage (enemy survives, ready). Jango's controller may exhaust that
# enemy unit → P1 says YES. Uses the real DeployLeader → attack execution path.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016;myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P1LEADER:DEPLOYED
