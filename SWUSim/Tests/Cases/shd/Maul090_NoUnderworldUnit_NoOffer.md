# SHD_090 Maul — with no OTHER friendly Underworld unit, there's no redirect offer (no decision), and Maul
# takes the counter normally. SOR_095 (Rebel, non-Underworld) is not a valid target.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
