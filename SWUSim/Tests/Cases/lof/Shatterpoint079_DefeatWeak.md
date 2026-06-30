# LOF_079 Shatterpoint — mode A: defeat a non-leader unit with 3 or less remaining HP. P1 picks the
# DefeatWeak mode and the enemy 3/1 (1 HP) is defeated.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_079}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatWeak

## EXPECT
P2GROUNDARENACOUNT:0
