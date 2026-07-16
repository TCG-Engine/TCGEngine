# AnotherCunning_GainsShielded
#// SHD_212 Privateer Scyk (2-cost, Cunning space) — "While you control another Cunning unit, this unit gains
#// Shielded." Guard: with another friendly Cunning unit (SHD_186) in play, it has Shielded.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SHD_212:1:0
WithP1GroundArena: SHD_186:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_212
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
