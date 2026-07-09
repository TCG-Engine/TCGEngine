# SHD_232 Relentless Pursuit — the capture only hits an enemy costing ≤ the captor's cost. SHD_138 Jango
# (cost 4, Bounty Hunter) cannot capture the cost-5 SHD_190, so nothing is captured, but Jango (a Bounty
# Hunter) still gets its Shield.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: SHD_138:1:0
WithP2GroundArena: SHD_190:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_190
P1GROUNDARENAUNIT:0:CARDID:SHD_138
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
