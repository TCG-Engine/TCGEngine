# SHD_188 4-LOM (4-cost, Cunning/Villainy) — Ambush + "Each friendly unit named Zuckuss gets +1/+1 and gains
# Ambush." With 4-LOM in play, the friendly Zuckuss (SHD_190, base 6/6) becomes 7/7 and has Ambush.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_188:1:0
WithP1GroundArena: SHD_190:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_190
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
