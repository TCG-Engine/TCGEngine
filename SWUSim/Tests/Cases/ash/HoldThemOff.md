# SplitPowerInArena
#// ASH_139 Hold Them Off (Event, cost 4) — Choose a friendly unit; it deals damage equal to its power
#// divided among any number of units in its arena. P1 picks SOR_046 (3 power) and assigns all 3 to the
#// enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3
## EXPECT
P2GROUNDARENACOUNT:0
