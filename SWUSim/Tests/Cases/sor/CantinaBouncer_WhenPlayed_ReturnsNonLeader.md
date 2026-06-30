# SOR_202 Cantina Bouncer (3/5, Ground) — When Played: You may return a non-leader unit to
# its owner's hand (either player's). P1 plays it and returns the enemy Battlefield Marine,
# which goes back to P2's hand and leaves P2's ground arena empty.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP2GroundArena: SEC_080:1:0    # enemy non-leader unit — returned to P2's hand

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
