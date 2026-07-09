# SHD_186 Hunter of the Haxion Brood — negative guard: with no enemy Bounty unit, it does NOT have Shielded.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_186
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded
