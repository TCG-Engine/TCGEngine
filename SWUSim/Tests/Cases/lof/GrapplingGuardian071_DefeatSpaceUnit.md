# LOF_071 Grappling Guardian — When Played: may defeat a space unit with 6 or less remaining HP. P1 plays
# it and defeats the enemy 2/1 TIE Fighter.

## GIVEN
CommonSetup: bbk/ggw/{myResources:7;handCardIds:LOF_071}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
