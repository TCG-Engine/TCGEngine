# SEC_180 Let's Call It War (Event, Aggression, cost 3) — deal 3 to a unit; then if you have the
#   initiative, may deal 2 to another unit in the same arena.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION
