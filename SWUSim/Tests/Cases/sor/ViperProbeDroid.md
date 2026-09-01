# WhenPlayed_LookAtHand
#// SOR_228 Viper Probe Droid (Unit, cost 2, Villainy) — "When Played: Look at an opponent's hand."
#// Pure information: P1 plays Viper; P2's hand (2 cards) is shown to P1 as an acknowledge popup
#// (card images + an OK button) and logged. Nothing is discarded — P2's hand is unchanged.
#// COVERAGE: offer=WhenPlayed_RevealPopupIsPendingForCaster (no target pick — the reveal is an
#//           acknowledge popup, not a choose; the popup itself is left PENDING and its prompt read,
#//           since an OK answered against no pending decision is silently absorbed and would prove
#//           nothing) · decline=N/A (mandatory look, no "you may") ·
#//           control=EnemyPlaysViper_LooksAtYOURHand (who RESOLVES it: the reveal follows the player
#//           who played the droid, and the seat being looked at gets nothing; no persistent effect
#//           exists for an owner/controller split to act on) · boundary=EmptyEnemyHand_StillPlayable
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

---

# WhenPlayed_RevealPopupIsPendingForCaster
#// SOR_228 Viper Probe Droid — WhenPlayed_LookAtHand ANSWERS the acknowledge popup with OK, and an
#// AnswerDecision against no pending decision is silently absorbed: that section therefore only
#// TOLERATES the reveal, it cannot prove it happened. (That is exactly how the reprint shipped as a
#// log-line-only stub — bug #1028 — and no test went red.)
#// Here the popup is left PENDING and read directly: P1 has a decision whose prompt is the hand
#// reveal, "Opponent's hand". The look is pure information, so P2's 2-card hand is untouched and P2
#// is never asked anything.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_228
WithP2Hand: SEC_080
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Opponent's_hand
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2NODECISION

---

# EnemyPlaysViper_LooksAtYOURHand
#// SOR_228 Viper Probe Droid — "Look at AN OPPONENT'S hand" resolves for whoever PLAYED the droid, and
#// the entitlement runs one way only. Every other section in this file has P1 as the caster, which a
#// seat-hardcoded reveal would also satisfy. Here P2 plays Viper: the reveal popup lands on P2 (the
#// caster), P1 — whose hand is being looked at — gets NO decision and learns nothing, and P1's hand is
#// unchanged at 2 cards. Viper seats in P2's ground arena.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_228
WithP1Hand: SEC_080
WithP1Hand: SOR_171

## WHEN
- P2>PlayHand:0

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Opponent's_hand
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_228
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1NODECISION
