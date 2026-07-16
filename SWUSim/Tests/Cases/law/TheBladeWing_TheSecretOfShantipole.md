# BounceUnit
#// LAW_241 The Blade Wing (Cunning, cost 6, space) — When Played: you may return a non-leader unit to
#// its owner's hand. Return the enemy SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
