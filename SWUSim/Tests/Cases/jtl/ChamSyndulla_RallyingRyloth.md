# WhenPlayed_OpponentControlsMore_RampsResource
#// JTL_164 Cham Syndulla, Rallying Ryloth — When Played: If an opponent controls MORE resources than you,
#// you may put the top card of your deck into play as a resource. P1 (4 resources) plays Cham while P2
#// controls 5 → condition met → YES → top of deck (SOR_095) enters as a resource. P1 now controls 5, deck empty.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP2Resources: 5
WithP1Hand: JTL_164
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_164
P1RESCOUNT:5
P1DECKCOUNT:0

---

# WhenPlayed_EqualResources_NoRamp
#// JTL_164 Cham Syndulla — the negative case: if the opponent does NOT control more resources than you,
#// the ability does nothing (no YES/NO prompt). P1 (5 resources) plays Cham while P2 also controls 5 →
#// 5 is not more than 5 → no trigger → top of deck (SOR_095) stays in the deck, resource count unchanged.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2Resources: 5
WithP1Hand: JTL_164
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_164
P1RESCOUNT:5
P1DECKCOUNT:1
P1NODECISION
---

# TwinSuns_ANYRicherOpponentSatisfiesTheCondition
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). Re-filed out of PROMPT (47): "if an
#// opponent controls more resources than you" is an EXISTENTIAL CONDITION, not a target — the effect
#// ("put the top card of YOUR deck into play as YOUR resource") never refers back to the opponent, so
#// nothing downstream needs to know which one. A picker here would be its own I1 violation.
#// ⚠ On this card the CONDITION and the GATE are the same line, which is unusual for this sweep — there
#//   is no second site to fix, so do not go looking for one.
#// P1 controls 4 resources. SEAT 2 — the only seat the old code compared against — also controls 4, so
#// the old code offered nothing. SEAT 3 controls 6, which IS more, so the offer must be raised and P1 ramps.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent OtherPlayer() is the only comparison there is.
#// Mutation check: revert to OtherPlayer() and this reds while both 2-player sections stay green.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Resources: 4
WithP2Resources: 4
WithP3Resources: 6
WithP4Resources: 4
WithP1Hand: JTL_164
WithP1Deck: SOR_095
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P1RESCOUNT:5
P1DECKCOUNT:0
