# LOF_229 Kylo Ren (2/3) — Overwhelm + "When you play an upgrade on this unit: may use the Force → draw a
# card." P1 plays Resilient (SOR_069) onto Kylo; the reaction lets P1 use the Force and draw.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_229:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
