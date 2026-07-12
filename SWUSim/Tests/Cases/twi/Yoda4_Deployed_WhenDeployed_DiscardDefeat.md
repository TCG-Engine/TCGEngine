# TWI_004 Yoda (Leader, deployed) — Restore 2 + "When Deployed: You may discard a card from your deck. If
# you do, defeat an enemy non-leader unit that costs the same as or less than the discarded card." The top
# of P1's deck is SOR_046 (cost 3); discarding it defeats the enemy SOR_095 (cost 3).
## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myLeader:TWI_004}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
