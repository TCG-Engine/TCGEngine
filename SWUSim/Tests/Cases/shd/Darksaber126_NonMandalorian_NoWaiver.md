# SHD_126 The Darksaber — the waiver is host-conditional. Attaching to a NON-Mandalorian unit (SOR_046)
# keeps the +2 off-Command penalty, so the cost is 6; with only 4 resources the play fails and nothing
# attaches (SOR_046 stays a 3-power unit, resources untouched).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:POWER:3
