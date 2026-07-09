# Uniqueness — pilot vs pilot (CR 29.3.a). A unique PILOT counts toward the copy limit whether it is a
# ground/space unit OR attached to a Vehicle as a pilot upgrade. JTL_035 Tam Ryvora (unique Pilot, unit
# cost 3, Piloting cost 2 Vigilance/Villainy, +2/+2; its only text is Piloting + a granted On-Attack, so
# nothing fires on play).
#
# P1 controls two Vehicles; Vehicle 0 already has a Tam Ryvora piloting it. P1 plays a second Tam Ryvora
# from hand as a pilot onto Vehicle 1. The pilot copy on Vehicle 0 is auto-defeated (keep the just-played
# one). No prompt.
#
# 2 resources: JTL_035's unit cost (3) is unaffordable → the play is pilot-only; Vehicle 0 is occupied so
# Vehicle 1 is the sole valid pilot target → auto-attaches with no picker. Iden (bk) covers Vigilance+Villainy.

## GIVEN
CommonSetup: bbk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaPilot: 0:JTL_035

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
# Vehicle 0's pilot auto-defeated.
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
# Freshly-played pilot now on Vehicle 1.
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_035
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_035
P1NODECISION
