# ASH_247 One Must Destroy to Create — declining the optional replay leaves the defeated unit in the
# discard pile. SOR_095 is defeated and P1 declines, so the arena is empty and the discard holds both the
# event and SOR_095.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
