# SEC_145 Confidence in Victory (Event, cost 10, Aggression/Villainy, Gambit)
#   "Play only as your first action in the action phase. Choose an arena. At the start of the regroup
#    phase, if you are the only player who controls units in that arena, you win the game."
# P1 plays it as its first action and chooses Ground. P1 controls a ground unit (SOR_095); P2 controls
# none. Both pass to the regroup phase, where the win check fires: P1 is the only player with units in
# the ground arena, so P1 wins.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P1WIN
