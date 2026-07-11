# SHD_112 Gamorrean Retainer (2-cost, Command) — "While you control another Command unit, this unit gains
# Sentinel." Guard: with another Command unit (SHD_083) in play it has Sentinel.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_112:1:0
WithP1GroundArena: SHD_083:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_112
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
