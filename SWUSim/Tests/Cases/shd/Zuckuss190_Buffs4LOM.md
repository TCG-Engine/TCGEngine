# SHD_190 Zuckuss (5-cost 6/6 ground) — Saboteur + "Each friendly unit named 4-LOM gets +1/+1 and gains
# Saboteur." With Zuckuss in play, the friendly 4-LOM (SHD_188, base 4/4) becomes 5/5 and has Saboteur.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_190:1:0
WithP1GroundArena: SHD_188:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_188
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
