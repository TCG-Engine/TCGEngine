# SOR_219 Sneak Attack — "At the start of the regroup phase, defeat it." P1 plays Sneak Attack to
# put SOR_095 into play (discounted, ready), then passes; with P1OnlyActions the opponent has already
# auto-passed, so the single P1 pass ends the action phase and RegroupPhaseStart defeats the
# Sneak-Attacked unit. The Marine leaves the arena (COUNT 0) and joins the event in P1's discard
# (the event SOR_219 + the defeated SOR_095 = 2).

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
