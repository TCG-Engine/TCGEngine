# TS26_034 Fives — the copy is optional ("you may"). Declining copies nothing, so only Fives enters play
# alongside the LAAT (ground count 2).
## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_034}
WithP1GroundArena: TS26_023:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:2
