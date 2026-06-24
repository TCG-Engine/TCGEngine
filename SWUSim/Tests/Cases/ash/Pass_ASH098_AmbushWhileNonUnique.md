# ASH_098 AT-ST Raider (Ground, 4/5) — While you control another non-unique unit, this unit gains Ambush.
# With a friendly non-unique SOR_095 present, AT-ST Raider has Ambush.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_098:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_098
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
