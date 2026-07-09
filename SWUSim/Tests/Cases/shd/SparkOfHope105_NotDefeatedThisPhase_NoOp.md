# SHD_105 Spark of Hope — the "defeated this phase" gate. A unit sitting in the discard that was NOT
# defeated this phase (seeded directly) is not a valid target: the event resolves with no choice, no
# resource ramp. Discard keeps SEC_080 + the played event; resources unchanged.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_105
WithP1Discard: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESCOUNT:6
P1DISCARDCOUNT:2
