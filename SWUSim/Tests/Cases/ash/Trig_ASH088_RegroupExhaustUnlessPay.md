# ASH_088 The Conflict Within (Upgrade/Condition) — Attached unit gains "When this unit readies: you may
# pay 3 resources. If you don't, exhaust this unit." Host SOR_095 starts exhausted; at the regroup ready
# step P1 declines to pay, so SOR_095 is exhausted again (stays exhausted).
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
