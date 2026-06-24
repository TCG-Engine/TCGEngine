# ASH_197 Executor (Space, 5/12, cost 8) — This unit gets +1/+0 for each upgrade on OTHER friendly
# units. When Played: give an Advantage token to each other friendly unit. P1 controls SOR_095 (with a
# SOR_120 upgrade) and SOR_237. Playing Executor gives each of them 1 Advantage token; Executor's power
# is then 5 + upgrades-on-others = 5 + (SOR_120 + Advantage on SOR_095 = 2) + (Advantage on SOR_237 = 1)
# = 8. (Value 8 distinguishes: ignoring the new Advantage tokens as upgrades would give 6.)
## GIVEN
CommonSetup: yyk/yyk/{myResources:8;handCardIds:ASH_197}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:1:CARDID:ASH_197
P1SPACEARENAUNIT:1:POWER:8
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:0
