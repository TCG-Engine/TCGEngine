# FirstEventBlanked
#// SOR_089 Relentless (8/8) — "The first event played by each opponent each round loses all abilities."
#// P1 controls Relentless; P2 plays Confiscate (its first event of the round) targeting P1's only upgrade.
#// The event is blanked, so the upgrade (SOR_120 on SEC_080) is NOT defeated.
#//
#// COVERAGE: offer=N/A (passive aura — SOR_089 itself raises no target choice; the blanked event's own
#//           choices simply never appear, asserted via P2NODECISION in BlankedBamboozle_NoExhaustNoBounce)
#//           decline=N/A (nothing optional on SOR_089) · control=N/A (the per-round "event played" flag
#//           is seat-global, not stamped on Relentless; no control-change interaction exists to pin)
#//           boundary=NextRound_FirstEventBlankedAgain + SecondEventNotBlanked (the round-boundary pair:
#//           2nd event same round resolves, 1st event next round is blanked again)
#//           reqboundary=PlayedAfterFirstEvent_SecondEventStillNotBlanked + NextRound_* (the
#//           first-event-this-round flag is read across many separate requests/actions)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1

---

# NoRelentless_EventResolves
#// SOR_089 Relentless — control: without Relentless, P2's Confiscate resolves normally and defeats P1's
#// upgrade.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1

---

# SecondEventNotBlanked
#// SOR_089 Relentless — only the FIRST event each round is blanked. P2 plays Confiscate (1st event,
#// blanked → upgrade survives), P1 passes, then P2 plays a second Confiscate (NOT blanked) which defeats
#// the upgrade. The end state (upgrade gone) plus Relentless_FirstEventBlanked together prove "first only."

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Hand: SOR_251
WithP2Resources: 2

## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2

---

# ControllersOwnEvent_NotBlanked
#// SOR_089 Relentless — the blank reads "played by each OPPONENT": the controller's own events are
#// untouched. P1 controls Relentless and plays its own Confiscate, which resolves normally and
#// defeats P2's upgrade (single upgraded unit → auto-target).

## GIVEN
SkipPreGame: true
CommonSetup: bbk/ggw
WithActivePlayer: 1
WithP1SpaceArena: SOR_089:1:0
WithP1Hand: SOR_251
WithP1Resources: 1
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# PlayedAfterFirstEvent_SecondEventStillNotBlanked
#// SOR_089 Relentless — the "first event each round" count is per-ROUND, not per-Relentless: an event
#// played BEFORE Relentless entered play already consumed the round's first-event slot, so the next
#// event after Relentless arrives is the round's second and is NOT blanked. P2 plays Confiscate #1
#// (no Relentless yet → resolves, strips SEC_080's upgrade), P1 plays Relentless (Command/Villainy,
#// 9 on-aspect under ggk), then P2's Confiscate #2 also resolves and strips SOR_095's upgrade.

## GIVEN
SkipPreGame: true
CommonSetup: ggk/brw
WithActivePlayer: 2
WithP1Hand: SOR_089
WithP1Resources: 9
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2Hand: SOR_251
WithP2Hand: SOR_251
WithP2Resources: 2

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0.u0
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_089
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2DISCARDCOUNT:2

---

# NextRound_FirstEventBlankedAgain
#// SOR_089 Relentless — the first-event blank resets each ROUND. R1: P2's Confiscate #1 is blanked
#// (upgrade survives); the round crosses regroup (P1 claims; both decline the optional resource; both
#// decks seeded so the regroup draws don't hit an empty deck). R2: P2's Confiscate #2 is the FIRST
#// event of the new round, so it is blanked AGAIN — the upgrade survives both plays. P2's resources
#// readied at regroup and end spent again (P2RESAVAILABLE:0), proving a blanked event still pays its
#// full cost.

## GIVEN
SkipPreGame: true
CommonSetup: ggw/brw
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Hand: SOR_251
WithP2Resources: 1
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P2>PlayHand:0
- P1>Claim
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:2
P2RESAVAILABLE:0
LOGCONTAINS:loses all abilities

---

# SmuggledEvent_BlankedAfterCostPaid
#// SOR_089 Relentless — an event played via Smuggle is still "the first event played" and gets
#// blanked, but the Smuggle COST is not an ability of the played event and was already paid to
#// initiate the play. P2 smuggles SHD_129 Timely Intervention (Smuggle 2, Command — covered by ggw)
#// out of its resource row: the smuggle cost is exhausted and the resource is replaced from the top
#// of the deck (RESCOUNT stays 4), but the event's own effect is blanked — the unit in P2's hand is
#// NOT played. Intended: cost paid, effect nullified.

## GIVEN
SkipPreGame: true
CommonSetup: ggk/ggw
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP2Resources: 1:SHD_129,3:SOR_095
WithP2Hand: SOR_095
WithP2Deck: SOR_128

## WHEN
- P2>SmuggleResource:0

## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P2RESCOUNT:4
P2DECKCOUNT:0
P2RESAVAILABLE:2
P2NODECISION

---

# BlankedBamboozle_NoExhaustNoBounce
#// SOR_089 Relentless — P2's first event of the round is SOR_199 Bamboozle ("You may discard a
#// [Cunning] card instead of paying this event's cost. Exhaust a unit and return each upgrade on it
#// to its owner's hand."). Blanked, it does nothing: P1's upgraded SEC_080 stays READY and keeps its
#// upgrade, and no decision of any kind is raised for P2 (the alternate-cost route is treated as part
#// of the lost abilities — same engine treatment as the SEC_046 Galen blank — so the discard prompt
#// never appears and SOR_210 stays in hand). Resource accounting for a blanked alt-cost event is a
#// recorded known limitation and is deliberately NOT asserted here.

## GIVEN
SkipPreGame: true
CommonSetup: ggk/yyw
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_199
WithP2Hand: SOR_210
WithP2Resources: 2

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2NODECISION
