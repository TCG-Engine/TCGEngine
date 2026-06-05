# SOR_051 Luke Skywalker (Unit 6/7, cost 7, Vigilance/Heroism, Restore 3) — "When Played: Give an
# enemy unit -3/-3 for this phase. If a friendly unit was defeated this phase, give that enemy unit
# -6/-6 for this phase instead." No friendly unit has been defeated this phase, so the basic -3/-3
# applies. The single enemy (AT-ST, 6/7) auto-resolves → 3/4 for the phase.

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:4
