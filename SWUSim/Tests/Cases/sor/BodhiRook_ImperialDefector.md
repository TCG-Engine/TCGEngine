# DiscardNonUnit
#// SOR_201 Bodhi Rook (Unit, cost 3, Cunning) — "When Played: Look at an opponent's hand and discard
#// a NON-UNIT card from it." P2's hand is a unit (SOR_095) + an event (SOR_171). Only the event is a
#// valid target, so the discard auto-resolves on it (single legal target). Because there's no
#// MZCHOOSE, the auto-discard resolves and a saved snapshot of P2's hand is then shown as an
#// acknowledge popup (Viper-style); after the OK the unit stays in hand and nothing is pending.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND

---

# DiscardNonUnitFromMany
#// SOR_201 Bodhi Rook (Unit, cost 3, Cunning) — "When Played: Look at an opponent's hand and discard
#// a NON-UNIT card from it." P2's hand is a unit (SOR_095) + an event (SOR_171). Only the event is a
#// valid target, so the discard auto-resolves on it (single legal target). The unit stays in hand.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_171
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND

---

# OnlyUnits_NoDiscard
#// SOR_201 Bodhi Rook — non-unit filter guard: P2's hand is all units (SOR_095, SOR_128), so there
#// is no valid non-unit card to discard → the discard fizzles (nothing leaves P2's hand). The "look
#// at an opponent's hand" still happens, so Bodhi shows P2's hand as an acknowledge popup; after the
#// OK no decision is left pending. Bodhi still enters play.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION
LOGCONTAINS:looked at

---

# SavedHandShownAfterAutoDiscard
#// SOR_201 Bodhi Rook (Unit, cost 3, Cunning) — "When Played: Look at an opponent's hand and discard
#// a NON-UNIT card from it." With exactly ONE non-unit target the discard auto-resolves (no MZCHOOSE),
#// so the player never sees the hand. Behavior: a snapshot of the hand is SAVED before the auto-discard,
#// the discard resolves, and the saved snapshot is then shown as a Viper-Probe-Droid (SOR_228) OK popup.
#// This test stops BEFORE answering the popup: the discard has ALREADY happened (P2DISCARDCOUNT:1) and
#// the saved-hand popup is pending (P1HASDECISION) — proving the popup confirms AFTER the auto-discard,
#// not gating it.

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
