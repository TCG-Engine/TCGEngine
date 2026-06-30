# SHD_262 Confiscate — reprint of SOR_251 behaves identically
# Single upgraded unit → auto-defeats upgrade.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SHD_262}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
