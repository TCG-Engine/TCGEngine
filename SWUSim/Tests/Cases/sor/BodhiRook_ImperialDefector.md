# DiscardNonUnit
#// COVERAGE: offer=N/A - STRUCTURAL: the discard is chosen from an OPPONENT'S HAND, a hidden zone
#//           rendered through the look-at-hand reveal rather than a selectable board pool; the scope is
#//           asserted behaviourally by OnlyUnits_NoDiscard (a hand of units yields nothing).
#//           decline=N/A - STRUCTURAL: printed mandatory, no "you may".
#//           boundary=N/A - no numeric threshold in the text.
#//           control=N/A - STRUCTURAL: a When Played on a unit, so it resolves once for whoever played
#//           it; there is no later re-resolution for control to change.
#//           reqboundary=SavedHandShownAfterAutoDiscard (the saved hand must survive to the reveal)
#//           modes=2P; ⚠ TwinSuns NOT covered - "an opponent's hand" is a PROMPT above two seats and no
#//           far-seat section exists. Open cell.
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

---

# TwinSuns4P_CasterPicksWhichOpponentsHand_FilterAppliesToThatHand
#// SOR_201 — "look at AN OPPONENT's hand and discard a NON-UNIT card from it": the caster picks whose, and the
#// non-unit filter applies to THAT hand (the filter crosses the pick through the card's options function,
#// _SWUOppDiscardOpts_SOR_201 — a closure cannot ride the decision queue). P3 holds one non-unit (SOR_171) and a
#// unit; P2 holds a non-unit too. P1 picks P3: P3's event is discarded (the single legal target auto-resolves),
#// P3 keeps the unit, P2 is untouched.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: [SOR_171]
WithP3Hand: [SOR_171 SOR_095]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:1
P3DISCARDCOUNT:1
P3DISCARDUNIT:0:CARDID:SOR_171
P2HANDCOUNT:1
P2DISCARDCOUNT:0
