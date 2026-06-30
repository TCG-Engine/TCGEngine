# SEC_053 One in a Million (Event, Plot, cost 1, Vigilance/Heroism)
#   "This card can't be played from your hand. Defeat a unit with power and remaining HP both
#    equal to the number of ready resources you control. Plot"
# This test: the hand-play RESTRICTION. P1 has SEC_053 in hand, affords it (3 ready resources),
# and the aspects are covered (bbw = Vigilance base + Luke Vig/Heroism leader) — so a NORMAL event
# would play. SEC_053 must NOT: the play is a no-op, the card stays in hand, no cost paid, P1 keeps
# its action. (The Plot-from-resources path is exercised by the other two cases.)

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_053

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:3
P1NODECISION
