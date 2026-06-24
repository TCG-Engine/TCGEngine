# ASH_229 Camtono (Upgrade, cost 1) — Attached unit gains: "When Attack Ends: look at the top card of your
# deck; if it costs 2 or less, you may play it for free." SOR_046 wears the Camtono and attacks P2's base;
# the top card SOR_095 (cost 2) is played for free, so P1's ground arena goes to 2 units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [SOR_095 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:2
