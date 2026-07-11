# SHD_126 The Darksaber (Upgrade, cost 4, Command, +4/+3) — "While playing this upgrade on a Mandalorian
# unit, ignore its aspect penalty." P1's base is off-Command (Aggression), so SHD_126 would normally cost
# 4 + 2 penalty = 6; attaching to the Mandalorian SOR_142 waives the penalty, so it costs exactly 4 (all
# of P1's resources) and SOR_142 becomes a 6-power unit wearing the Darksaber.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_142:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:POWER:6
