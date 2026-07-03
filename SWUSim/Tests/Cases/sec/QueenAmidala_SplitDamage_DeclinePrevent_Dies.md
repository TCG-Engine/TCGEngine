# SEC_101 Queen Amidala — SPLIT damage, DECLINE the prevention. Same 1/2/2 split from SOR_092 as the
# take-branch test, but P2 declines Amidala's optional prevent (AnswerDecision:-). The declined hit is
# re-parked and applied with the rest, so Amidala's 1 lands (2 → 3 = lethal) and both Spies take their 2
# (lethal) — all three P2 units are defeated. Proves the decline path applies the damage simultaneously.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_101:1:2
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:2,theirGroundArena-2:2
- P2>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
