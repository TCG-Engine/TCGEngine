# LOF_220 Shien Flurry — Play a Force unit from hand; it gains Ambush this phase and the next time it would
# be dealt damage, prevent 2. Plo Koon enters, Ambush-attacks SOR_046 (3/7) for 6; the 3 counter damage is
# reduced to 1 by the prevention.

## GIVEN
CommonSetup: yyw/ggk/{myResources:12;handCardIds:LOF_220,LOF_050}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:6
