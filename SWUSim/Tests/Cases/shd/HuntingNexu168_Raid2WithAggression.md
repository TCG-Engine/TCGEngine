# SHD_168 Hunting Nexu (4-cost, Aggression) — "While you control another Aggression unit, this unit gains
# Raid 2." Guard: with another Aggression unit (SHD_138) in play it has Raid.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_168:1:0
WithP1GroundArena: SHD_138:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_168
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
