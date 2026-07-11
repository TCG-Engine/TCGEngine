# Uniqueness — play the PILOT form while the unit copy is in an arena (CR 29.3.a). P1 controls a Tam
# Ryvora (JTL_035) as a ground unit. P1 plays a second Tam Ryvora from hand as a pilot upgrade onto a
# Vehicle. The ground-unit copy is auto-defeated (keep the just-played pilot). No prompt.
#
# 2 resources: JTL_035's unit cost (3) is unaffordable → pilot-only; the lone Vehicle is a valid target
# → auto-attaches with no picker. Iden (bk) covers Vigilance+Villainy.

## GIVEN
CommonSetup: bbk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: JTL_035
WithP1GroundArena: JTL_035:1:0
WithP1SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
# The pre-existing unit copy was auto-defeated…
P1GROUNDARENACOUNT:0
# …and the freshly-played pilot is now on the Vehicle.
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_035
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_035
P1NODECISION
