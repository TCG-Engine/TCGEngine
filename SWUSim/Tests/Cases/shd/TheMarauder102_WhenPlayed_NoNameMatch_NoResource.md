# SHD_102 The Marauder — the ramp is conditional on a shared name. P1 controls a Wampa (SOR_164) but
# the discard card is a Battlefield Marine (SOR_095) — different names. The choose still resolves, but
# the card is NOT put into play as a resource: it stays in the discard, resources unchanged.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_102
WithP1GroundArena: SOR_164:1:0
WithP1Discard: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1RESCOUNT:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
