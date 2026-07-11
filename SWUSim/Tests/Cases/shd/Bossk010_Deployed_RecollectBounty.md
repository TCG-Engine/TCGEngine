# SHD_010 Bossk (deployed) — "When you collect a bounty: You may collect that bounty again. Use this
# ability only once each round." P1's deployed Bossk-controller defeats the enemy SHD_095 (Clone Deserter,
# draw-1 Bounty) with SOR_046, collects the bounty (draw 1), then Bossk lets P1 collect it AGAIN (draw 1
# more) — 2 cards drawn total.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095 SOR_095 SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2
