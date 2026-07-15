# TS26_033 Kouhun Assassination — if the opponent declines to discard ("may"), the rider does not happen:
# no debuff, the opponent keeps their card and unit.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_033;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1
