# SHD_242 Gideon's Light Cruiser — no Moff Gideon controlled → When Played does nothing.
# P1 has a Vader (rrk) setup with no Moff Gideon. Playing SHD_242 (12 resources cover the off-aspect +2)
# resolves with no free-play offer: SEC_080 stays in hand, no decision pending.

## GIVEN
CommonSetup: rrk/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1NODECISION
