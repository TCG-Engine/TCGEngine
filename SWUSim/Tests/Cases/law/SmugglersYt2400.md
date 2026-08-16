# PayBuff
#// LAW_148 Smuggler's YT-2400 (4/5, space, Ambush) — When Played: you may pay 1 resource. If you do,
#// this unit gets +1/+1 for this phase. No enemy (Ambush no trigger); pay 1 -> 5/6.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6

---

# DeclinePayNoBuff
#// LAW_148 Smuggler's YT-2400 — the When Played pay is optional; declining leaves it a base 4/5 and
#// costs no extra resource. Play it (no enemy so Ambush is moot) and decline the pay.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5

---

# PayBuff_SurvivesTheRequestBoundary
#// LAW_148 Smuggler's YT-2400 — the "you may pay 1 resource" prompt is raised while the unit is entering
#// play and answered in a FRESH process, so the identity of the just-played unit that the +1/+1 must attach
#// to (and the resource payment behind the answer) has to be re-read from the serialized gamestate.
#// Mirrors PayBuff with a request boundary inserted between the play and the YES.
#// A YESNO decision is confirmed pending at that point (asserting P1NODECISION there reports type YESNO),
#// so the boundary is not a no-op.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
