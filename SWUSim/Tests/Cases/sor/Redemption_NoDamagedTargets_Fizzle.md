# SOR_052 — no damaged units or bases anywhere: the heal has no targets, so no decision is queued
# and Redemption simply enters at full HP. Absence guard for the empty-target path.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION
