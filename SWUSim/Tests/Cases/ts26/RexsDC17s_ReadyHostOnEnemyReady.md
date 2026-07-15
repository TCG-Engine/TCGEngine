# TS26_063 Rex's DC-17s (Upgrade +3/+2) — attached unit gains "When an enemy unit readies during the
# action phase: ready this unit (once each round)." Chaotic Diversion readies the exhausted enemy SOR_128,
# which readies the host SEC_080 (wearing the DC-17s).
## GIVEN
CommonSetup: ryk/rrk/{myResources:1;handCardIds:TS26_031}
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:TS26_063
WithP2GroundArena: SOR_128:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:READY
