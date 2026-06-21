# LOF_137 Savage Opress — with the Force, P1 uses it (YES) and avoids the 9 self-damage.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1BASEDMG:0
