# WhenPlayed_CreatesForce
#// LOF_193 Youngling Padawan — "When Played: The Force is with you (create your Force token)." Playing it
#// from hand creates P1's Force token.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_193}

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
