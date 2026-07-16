# RaidWhileMandalorian
#// ASH_105 Bo-Katan Kryze (Ground, 2/4) — While you control another Mandalorian unit, this unit gains
#// Raid 2. With a friendly Mandalorian (ASH_216) present, Bo-Katan has Raid.
## GIVEN
CommonSetup: ggw/ggk
WithP1GroundArena: ASH_105:1:0
WithP1GroundArena: ASH_216:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_105
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
