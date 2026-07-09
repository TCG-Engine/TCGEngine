# SHD_190 Zuckuss — negative guard: without a friendly Zuckuss in play, 4-LOM (SHD_188) keeps its printed
# 4/4 and does not have Saboteur.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_188:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_188
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
