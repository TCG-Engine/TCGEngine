# SOR_135 — When Played with no enemy units: the split fizzles (no decision queued, no damage),
# and Palpatine still enters play. Absence guard for the empty-target early-return.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_135
P1NODECISION
