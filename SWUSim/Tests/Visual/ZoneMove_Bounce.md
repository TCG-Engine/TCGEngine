# VISUAL CHECK — zone slide, arena -> hand (bounce)
#
# Visual-only schema. IC27_158 Millennium Falcon: "When Attack Ends: You may pay [1 resource]. If you
# do, return a friendly unit that costs 3 or less to its owner's hand."
#
# What to look at:
#   • Answer YES to the pay prompt; the Battlefield Marine flies from the ground arena into the hand.
#   • Then answer YES again to replay it for free — it flies straight back OUT to the arena.
#   • That out-and-back pair is a good single clip for a demo reel.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
