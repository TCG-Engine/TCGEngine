# LAW_077 Shadow of Stygeon Prime (Upgrade) — "Attach to a non-leader unit. Attached unit can't ready.
# It gains: 'When the regroup phase starts: Deal 2 damage to your base.'" SEC_080 starts EXHAUSTED with
# the upgrade; after a full round the ready step does NOT ready it (can't ready) and the regroup-start
# trigger deals 2 to P1's base.

## GIVEN
CommonSetup: rrk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_077
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:2
