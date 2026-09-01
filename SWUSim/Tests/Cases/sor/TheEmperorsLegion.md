# ReturnDefeatedThisPhase
#// COVERAGE: offer=N/A (no choice — "each unit" is a mandatory sweep, no pool is ever offered) ·
#//           reqboundary=DefeatedLastPhase_NotReturned (defeats, a phase cross, and the play all
#//           arrive in separate requests) + SimulateRequestBoundary_DefeatedThisPhaseSurvives ·
#//           control=ControlChange_StolenCasualtyLandsInItsOWNERSPile_NotReturned (a P2-OWNED unit
#//           dying under P1's control goes to P2's pile and is NOT returned, while P1's own casualty
#//           in the same phase IS — the self-controlling pair) +
#//           MassDefeat_OpponentsWipe_ReturnsAllCasualties (the defeats come from the OPPONENT's
#//           event; the return still keys on the owner's pile) ·
#//           boundary pair=ReturnDefeatedThisPhase vs SeededDiscardNotReturned +
#//           DefeatedLastPhase_NotReturned (the this-phase window) · decline=N/A (no "you may").
#// The return keys on each ENTRY's defeat provenance (From='PLAY') plus the this-phase count —
#// a copy that arrived by hand-discard never returns (fixed 2026-08-14; see
#// HandDiscardedCopy_AfterDefeatReturned_NotReturnedAgain).
#// SOR_091 The Emperor's Legion — "Return each unit in your discard pile that was defeated this
#// phase to your hand." P1's SOR_128 (3/1) attacks P2's SEC_080 (3/3): both die (SOR_128 deals 3 =
#// lethal, takes 3 back). SOR_128 went to P1's discard as DEFEATED-this-phase. P1 then plays SOR_091
#// → SOR_128 returns to P1's hand. SEC_080 is in P2's discard (different pile) → untouched.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_091

---

# SeededDiscardNotReturned
#// SOR_091 The Emperor's Legion — gating guard: a unit sitting in your discard that was NOT defeated
#// THIS PHASE (seeded there) is NOT returned. With nothing defeated this phase, SOR_091 returns
#// nothing → the seeded SOR_128 stays in discard, hand stays empty (only the event resolves to discard).

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091;discardCardIds:SOR_128}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# DefeatedLastPhase_NotReturned
#// SOR_091 The Emperor's Legion — "defeated THIS phase" is a live phase window, not a lifetime
#// flag. Phase 1: SOR_128 trades with a Shoretrooper (both die). Phase 2: SOR_095 trades with
#// the second one, then Legion is played — only the phase-2 casualty (SOR_095) returns; the
#// phase-1 SOR_128 stays in the discard even though it WAS defeated. Decks are seeded so the
#// regroup draw (2 cards, SOR_046 each) does not hit an empty deck. In the second action phase
#// the opponent acts first (initiative order), hence the P2 pass before each P1 action.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0]
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1HANDCARD:2:SOR_095
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_128
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:2

---

# MassDefeat_OpponentsWipe_ReturnsAllCasualties
#// SOR_091 The Emperor's Legion — multiple casualties return in one resolution, and "defeated
#// this phase" does not care WHO defeated them: P2's SOR_043 Superlaser Blast wipes P1's
#// SOR_128 + SEC_080; P1's Legion then returns BOTH to hand. The event itself lands in P1's
#// discard as the only remaining entry.

## GIVEN
CommonSetup: rgk/byk/{
  myResources:7;
  handCardIds:SOR_091,SOR_091;
  theirResources:19;
  theirhandCardIds:SOR_043
}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArena: [SOR_128:1:0 SEC_080:1:0]

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1HANDCARD:1:SOR_128
P1HANDCARD:2:SEC_080
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_091
P1GROUNDARENACOUNT:0
P1NODECISION

---

# HandDiscardedCopy_AfterDefeatReturned_NotReturnedAgain
#// Candidate #9 fix guard (defeat provenance): the multiset counts CardIDs, not physical copies —
#// after the DEFEATED copy leaves the pile, a copy that arrived by HAND-DISCARD must not ride the
#// stale count. Flow: SOR_128 trades and dies (count 1) → Legion #1 returns it → Force Throw (self)
#// discards it FROM HAND → Legion #2 must return NOTHING (the only SOR_128 in the pile was
#// hand-discarded, not defeated). Pre-fix it came back every time.

## GIVEN
CommonSetup: rrk/rrk/{myResources:10;handCardIds:SOR_091,SOR_091,SOR_167}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>PlayHand:1
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:4

---

# SimulateRequestBoundary_DefeatedThisPhaseSurvives
#// SOR_091 The Emperor's Legion — the return itself offers no choice, but the state it reads (the
#// "defeated this phase" provenance/count) is written during an EARLIER action and read during the
#// play. In production those are two separate requests, so that bookkeeping must live in the
#// serialized gamestate rather than an in-memory global. Mirrors ReturnDefeatedThisPhase with the
#// boundary inserted between the trade and the Legion play — SOR_128 must still come back to hand.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_091

---

# ControlChange_StolenCasualtyLandsInItsOWNERSPile_NotReturned
#// SOR_091 The Emperor's Legion — "Return each unit in YOUR discard pile…". A defeated card always
#// goes to its OWNER's discard, so a unit P1 merely CONTROLS can never land in P1's pile and can never
#// be returned by P1's Legion — even though P1 controlled it when it died and P1 owns the Legion.
#// P1 fields a SOR_128 of their own PLUS a second SOR_128 that P2 OWNS (the end state after a
#// take-control effect; controlled units seat after the plain ones, so the stolen copy is index 1).
#// Both trade with a Dark Trooper in the same phase, then P1 plays Legion. Intended: exactly ONE
#// casualty comes back — P1's own — so P1's hand holds SOR_128 alone and P2's pile ends at 3 (two
#// Dark Troopers plus the stolen Stormtrooper). The section is self-controlling: it contains a
#// casualty that MUST return and an identically-named one that must not, so a controller-keyed pile
#// lookup reads P1HANDCOUNT:2, and a pile that ignores control entirely hands P1 the enemy's card.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaControlled: SOR_128:2
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_091
P2HANDCOUNT:0
P2DISCARDCOUNT:3
