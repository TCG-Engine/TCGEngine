# SEC_195 Arrest — "Your base captures an enemy non-leader unit." Tokens can't be captured: a token that
# would be captured is defeated and removed from play instead (never stored as a base captive, so it is
# NOT returned to its owner at regroup). P1's base "captures" P2's SEC_T01 Spy → the Spy is defeated to
# P2's discard, and P2's arena is empty with no base-captive to rescue.
## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
