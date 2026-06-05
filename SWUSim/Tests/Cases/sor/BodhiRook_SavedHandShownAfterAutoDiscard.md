# SOR_201 Bodhi Rook (Unit, cost 3, Cunning) — "When Played: Look at an opponent's hand and discard
# a NON-UNIT card from it." With exactly ONE non-unit target the discard auto-resolves (no MZCHOOSE),
# so the player never sees the hand. Behavior: a snapshot of the hand is SAVED before the auto-discard,
# the discard resolves, and the saved snapshot is then shown as a Viper-Probe-Droid (SOR_228) OK popup.
# This test stops BEFORE answering the popup: the discard has ALREADY happened (P2DISCARDCOUNT:1) and
# the saved-hand popup is pending (P1HASDECISION) — proving the popup confirms AFTER the auto-discard,
# not gating it.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HASDECISION
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
LOGCONTAINS:looked at
