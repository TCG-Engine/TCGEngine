# PlayUnitWithAmbush
#// SHD_129 Timely Intervention (1-cost event) — "Play a unit from your hand (paying its cost). Give
#// it Ambush for this phase." The marine (sole affordable hand unit → auto-pick) enters with granted
#// Ambush and ambush-attacks the enemy Dark Trooper: mutual 3-damage kill. Resources prove the cost
#// was paid (1 event + 2 marine = all 3 spent).
#// COVERAGE: offer=Offer_OnlyAffordableUnitsAreSelectable (pending P1SELECTABLEEXACT — an unaffordable
#//           hand unit is excluded while two payable ones stay in) ·
#//           reqboundary=RequestBoundary_AmbushGrantSurvivesTheUnitPick ·
#//           boundary=Offer_OnlyAffordableUnitsAreSelectable vs OneResourceShort_NoUnitIsPlayable (the
#//           exactly-affordable / one-short pair) plus AmbushGrantExpiresAtTheEndOfThePhase (the "for
#//           this phase" duration edge) ·
#//           decline=AmbushDeclined_UnitStaysHomeButKeepsTheKeyword (the Ambush trigger is the only
#//           "you may" on the card) ·
#//           control=N/A (the unit is played from your own hand into your own arena and never changes
#//           controller; the event has no opponent-facing clause) ·
#//           dispatch paths=PlayUnitWithAmbush (from hand) + Smuggle_PlayUnitWithAmbush (from resources).
#// NOTE: the unit pick is mandatory here — with at least one payable unit in hand there is no
#//       "choose nothing" answer, so there is no section for declining the PLAY itself.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Smuggle_PlayUnitWithAmbush
#// SHD_129 via its OTHER dispatch path — Smuggle [2 resources, Command]. The event is a RESOURCE, not a
#// hand card, so the whole ability has to run off the smuggle-play route: 4 ready resources, 2 pay the
#// Smuggle cost (the smuggled card itself exhausts toward its own cost), the remaining 2 pay for the
#// marine, and the spent resource slot is refilled from the top of the deck (count stays 4). The marine
#// still enters with granted Ambush and trades with the enemy Dark Trooper exactly as on the hand path.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3:SOR_095:1,1:SHD_129:1
WithP1Deck: SOR_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>SmuggleResource:3
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESCOUNT:4
P1RESAVAILABLE:0
P1DECKCOUNT:0

---

# Offer_OnlyAffordableUnitsAreSelectable
#// SHD_129 — "Play a unit from your hand" is gated on the unit actually being PAYABLE with what is left
#// after the event's own cost. P1 has 3 resources; the event eats 1, leaving 2. SOR_095 (2, Command/
#// Heroism — both covered) and SOR_237 (2, Heroism — covered) each cost exactly 2, so both are legal;
#// SOR_051 (7, Vigilance/Heroism — Vigilance is uncovered under a Command base + Command/Heroism leader,
#// so +2 → 9) is not. The decision is deliberately left PENDING so the OFFER itself is the assertion.
#// Two legal options keep the pick interactive — with one it would auto-resolve and there would be no
#// offer to read. Hand indexes are post-cleanup: the event has already left, so the units compact to 0/1/2.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP1Hand: SOR_237
WithP1Hand: SOR_051
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# OneResourceShort_NoUnitIsPlayable
#// SHD_129 boundary partner to Offer_OnlyAffordableUnitsAreSelectable: same two 2-cost units, one fewer
#// resource. 2 resources, the event takes 1, and 1 left cannot pay for either unit — so there is no legal
#// target at all, no decision is raised, both units stay in hand and the event simply goes to the discard
#// pile having done nothing. The single point of difference from the previous section is the resource
#// count, which is what makes the pair a boundary.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP1Hand: SOR_237
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_129
P1RESAVAILABLE:1

---

# AmbushDeclined_UnitStaysHomeButKeepsTheKeyword
#// SHD_129 decline branch — the granted Ambush is a "you may", so answering NO skips the ready-and-attack
#// entirely. The marine is still played and still paid for (all 3 resources gone), the enemy Dark Trooper
#// takes no damage, and the marine sits in the arena exhausted like any normally-played unit. The keyword
#// is still on it for the rest of the phase; declining spends the trigger, not the grant.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# RequestBoundary_AmbushGrantSurvivesTheUnitPick
#// SHD_129 spans two requests in production — the player picks the unit in one, then answers the Ambush
#// trigger in the next — so the "gains Ambush for this phase" grant has to be read back out of the
#// serialized gamestate rather than out of anything the first request left in memory. Two affordable
#// units keep the pick interactive; the boundary is inserted between the pick and the Ambush answer, and
#// the ambush attack must still happen (mutual 3-damage trade with the Dark Trooper).

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP1Hand: SOR_237
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P1RESAVAILABLE:0

---

# AmbushGrantExpiresAtTheEndOfThePhase
#// SHD_129 — the grant is explicitly "for this phase", so it must not survive into the next one. P1 plays
#// the event, declines the ambush attack (so the marine is still around to inspect), then the phase ends:
#// both players pass, both decline the regroup resource, and the new action phase starts. The marine is
#// readied by regroup as normal and no longer has Ambush. Both decks are seeded so crossing regroup does
#// not trigger the empty-deck base damage.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:NO
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# ChooseNothing_NoUnitIsPlayedAndNothingGainsAmbush
#// SHD_129 — USER RULING (2026-08-15): "play a unit from your HAND" is always DECLINABLE, because the
#// hand is a HIDDEN zone — a player is never forced to reveal that they were holding a playable unit.
#// P1 plays the event with two affordable units in hand and declines: nothing is played, the hand is
#// untouched, no Ambush is granted anywhere, and only the event itself reaches the discard. The event's
#// own cost is still spent — declining the effect is not taking the play back.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1Hand: [SHD_129 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_129
P1RESAVAILABLE:4

---

# ChooseNothing_NoUnitIsPlayedAndNothingGainsAmbush_ByConfirmingEmpty
#// ⚠ PASS-TWIN of ChooseNothing_NoUnitIsPlayedAndNothingGainsAmbush — byte-for-byte identical except the decline.
#// `-` and "PASS" are two DIFFERENT declines, and the client only ever submits "PASS" (all three decline
#// paths in Core/UILibraries*.js). Historically every decline test here answered `-`, so the path players
#// actually take was untested. This continuation (SHD_129#0) is one that does more than apply the pick, and
#// it now runs on a decline because SWUQueueMayChooseTarget defaults dontSkipOnPass to 1 — this twin is
#// what covers that. If the two declines ever diverge, one of the pair goes red.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1Hand: [SHD_129 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_129
P1RESAVAILABLE:4
