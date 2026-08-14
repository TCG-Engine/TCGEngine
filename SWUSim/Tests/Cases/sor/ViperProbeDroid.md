# WhenPlayed_LookAtHand
#// SOR_228 Viper Probe Droid (Unit, cost 2, Villainy) — "When Played: Look at an opponent's hand."
#// Pure information: P1 plays Viper; P2's hand (2 cards) is shown to P1 as an acknowledge popup
#// (card images + an OK button) and logged. Nothing is discarded — P2's hand is unchanged.
#// COVERAGE: offer=N/A (no target pick — the reveal is an acknowledge popup, not a choose;
#//           asserted via the OK answer here) · decline=N/A (mandatory look, no "you may") ·
#//           control=N/A (no persistent effect to follow a unit) · boundary=EmptyEnemyHand_StillPlayable
#//           (zero-card edge of the look) · reqboundary=N/A (the look resolves inside the play
#//           ceremony; nothing survives past the request)

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_228
WithP2Hand: SEC_080
WithP2Hand: SOR_171

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

# EmptyEnemyHand_StillPlayable
#// SOR_228 Viper Probe Droid — Intended: the unit is still playable when the opponent's hand is
#// EMPTY; the look has nothing to show and the play completes cleanly (no hanging popup/decision).
#// P1 plays Viper with P2 at 0 hand cards → Viper seats, no pending decision remains.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_228

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_228
P2HANDCOUNT:0
P1NODECISION
