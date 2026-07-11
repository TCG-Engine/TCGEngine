# Uniqueness — play the UNIT form while a pilot copy is attached (CR 29.3.a). P1 has a Tam Ryvora
# (JTL_035) piloting Vehicle 0. P1 plays a second Tam Ryvora from hand as a ground UNIT. The attached
# pilot copy is auto-defeated (keep the just-played unit). No prompt.
#
# 3 resources = JTL_035's unit cost. Vehicle 0 is already piloted, so there is no valid pilot target →
# the play resolves as a unit with no Unit/Pilot prompt. Iden (bk) covers Vigilance+Villainy.

## GIVEN
CommonSetup: bbk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArenaPilot: 0:JTL_035

## WHEN
- P1>PlayHand:0

## EXPECT
# The unit form entered the ground arena…
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_035
# …and the pilot copy on the Vehicle was auto-defeated.
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_035
P1NODECISION
