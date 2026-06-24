# ASH_263 The Way of the Mand'alor (Upgrade, cost 2) — costs 1 resource less to play on a Mandalorian
# unit. Played onto the Mandalorian ASH_216, it costs 1: 2 resources - 1 = 1 left.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_263}
WithP1GroundArena: ASH_216:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:CARDID:ASH_216
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
