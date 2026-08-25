# RevealDisaster_Draws
#// COVERAGE: offer=OfferExcludesNonDisasterCards (SELECTABLEEXACT — a non-Disaster EVENT and a
#//           non-Disaster UNIT are both excluded, two Disasters remain)
#//           decline=Decline_NoDisasterInDiscard_NoDraw (+ Decline_DisasterInDiscard_StillDraws, the
#//           OTHER limb of the OR — decline is not the same branch as "no draw")
#//           boundary=BothLimbsTrue_DrawsExactlyOne (one satisfied limb and two satisfied limbs both
#//           draw exactly 1 — the quantity, not a threshold)
#//           control=N/A — HMW_180's only ability is a When Played, which does not re-fire when
#//           control changes later; "your hand"/"your discard pile" are read once, by the player
#//           resolving it. Scope is instead pinned by TwinSuns_OnlyYourOwnDiscardCounts.
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_180 Stormchaser — Unit (Ground) 3/2, cost 2, [Aggression], Tusken, non-unique.
#// "When Played: You may reveal a Disaster card from your hand. If you do or if there's a Disaster
#//  card in your discard pile, draw a card."
#//
#// ⚠ THE CARD IS AN **OR**, not an "if you do". The draw fires when EITHER limb holds, so declining
#// the reveal still draws whenever a Disaster sits in the discard pile. Reading it as "If you do,"
#// (the far more common templating, and what the eye expects here) silently deletes the second limb
#// and is invisible in any section that leaves the discard pile empty.
#//
#// Disaster is an EVENT-only trait; HMW_193 Nightfall and SOR_174 Smoke and Cinders are both
#// Aggression Disasters. Base 'r' is Aggression, so Stormchaser costs its printed 2.
#// Every section seeds a deck: an empty deck turns "draw a card" into 6 base damage (CR 6.1) and the
#// failure then reads like a broken heal rather than a missing fixture.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_180
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# Decline_NoDisasterInDiscard_NoDraw
#// THE LOAD-BEARING NEGATIVE. With an empty discard pile the reveal is the ONLY limb, so declining it
#// must draw nothing. An implementation that draws unconditionally passes every positive section here
#// and is caught only by this one.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:3
P1DISCARDCOUNT:0
P1NODECISION

---

# Decline_DisasterInDiscard_StillDraws
#// ⚠ THE HEADLINE CELL — the second limb of the OR, standing on its own. Same board as the section
#// above except the discard pile holds a Disaster, and the SAME decline now draws.
#// Read as "If you do, draw" this section fails; read as the printed OR it passes. Nothing else in the
#// file distinguishes those two readings.
#// The discard seed is asserted directly (count + CardID) rather than assumed: an unrecognised
#// CommonSetup key is silently dropped, which would leave this testing the section above by accident.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;discardCardIds:SOR_174}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_174
P1HANDCOUNT:2
P1DECKCOUNT:2
P1NODECISION

---

# NoDisasterInHand_DisasterInDiscard_DrawsWithNoPrompt
#// The second limb with NOTHING to reveal at all: the hand holds a non-Disaster event, so there is no
#// offer to raise and the draw must simply happen. Asserting P1NODECISION is the point — a card that
#// raised an empty or pointless pick here would still draw and look correct.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;discardCardIds:SOR_174}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 SOR_251]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2
P1NODECISION

---

# NoDisasterAnywhere_NoDrawNoPrompt
#// NO-VALID-TARGET on both limbs at once: nothing to reveal, nothing in the discard. The ability must
#// resolve to nothing, cleanly, with no dangling decision. The discard is seeded with a NON-Disaster
#// (SOR_095, a unit) rather than left empty, so a gate that checks "is the discard pile non-empty"
#// instead of "does it hold a Disaster" fails here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;discardCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 SOR_251]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:3
P1NODECISION

---

# OfferExcludesNonDisasterCards
#// THE OFFER, asserted rather than answered — answering a card proves the branch, never the pool.
#// Hand after Stormchaser is played and the removed slot compacted away:
#//   myHand-0 HMW_193 Nightfall            Event, Disaster        → LEGAL
#//   myHand-1 SOR_174 Smoke and Cinders    Event, Disaster        → LEGAL
#//   myHand-2 SOR_251 Confiscate           Event, NOT a Disaster  → EXCLUDED
#//   myHand-3 SOR_128 Death Star Stormtrooper  Unit, NOT a Disaster → EXCLUDED
#// Two excluded cards of DIFFERENT kinds: a non-Disaster event proves the filter is the trait and not
#// merely "is it an event", and the unit proves it is not "any card". Two legal ones remain so the
#// pool is a real pool.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193 SOR_174 SOR_251 SOR_128]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1
P1DECKCOUNT:3

---

# RevealedCardIsTheCardYouChose
#// Identity, not just "a reveal happened": with a non-Disaster sitting at myHand-0 and the Disaster at
#// myHand-1, the LOGGED card name is what proves the answer resolved to the card the player picked
#// rather than to a neighbouring hand slot. Every other section here would pass just as happily if the
#// wrong card were revealed, since nothing else in the effect depends on which card it was.
#//
#// ⚠ This section does NOT pin a stale-hand-slot bug, and it was originally written believing it did.
#// The premise — "the just-played card lingers in the hand array, so the offer must be built after
#// CleanupRemovedCards" — is what ASH_132 Queen Soruna and LOF_150 Cin Drallig assert, but it does not
#// hold for a unit's When Played: the hand is already compacted by the time the entry-trigger flush
#// dispatches it. Removing the cleanup call changed nothing here, so the call was dropped rather than
#// kept as a no-op with a false comment attached (measured on both dispatch paths, 2026-08-24).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 SOR_251 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
LOGCONTAINS:Nightfall
P1HANDCOUNT:3
P1DECKCOUNT:2

---

# BothLimbsTrue_DrawsExactlyOne
#// QUANTITY DISCRIMINATION. Reveal a Disaster WHILE a Disaster also sits in the discard pile: both
#// limbs of the OR hold, and the card draws "a card" — exactly ONE. An implementation that draws once
#// per satisfied limb passes every other section in this file and lands on HANDCOUNT 3 / DECKCOUNT 1
#// here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;discardCardIds:SOR_174}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2

---

# RevealFiresThePadmeReaction
#// ⚠ THIS SECTION IS WHY THE PROMPT IS ALWAYS OFFERED. SEC_016 Padmé Amidala reads "When you reveal or
#// discard 1 or more cards from your hand: You may exhaust this leader. If you do, deal 1 damage to a
#// unit." So revealing is NOT strictly worse than declining — it can be worth real value — which rules
#// out auto-declining the reveal whenever the discard pile already guarantees the draw.
#// Padmé is Cunning+Heroism and the base is Aggression, so Stormchaser still costs its printed 2.
#// Stormchaser is the only unit in play, so Padmé's damage target auto-resolves onto it (3/2, survives
#// on 1 damage).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SEC_016;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_180
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
P1HANDCOUNT:2
P1DECKCOUNT:2

---

# PlayedByTheWarrior_WhenPlayedStillResolves
#// DISPATCH PATH: Stormchaser reached NOT by a hand play but by another card's effect — HMW_018 The
#// Warrior's "Action [1 resource, Exhaust]: Play a unit with 3 or less power from your hand". At power
#// 3 Stormchaser is exactly on her boundary, so the two cards genuinely meet in play.
#// The nested play must still fire Stormchaser's own When Played (offer, reveal, draw) AND still let
#// The Warrior's rider stamp Ambush on it. A card that only works when played directly from hand
#// passes every other section in this file.
#// Enemy board is empty so the granted Ambush finds no target and adds no trigger, leaving the reveal
#// as the only decision to answer.
#// Resources 6 → the leader action pays 1 (5 left) → Stormchaser costs its printed 2 (3 left).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_018;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_180
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1HANDCOUNT:2
P1DECKCOUNT:2
P1RESAVAILABLE:3
P1LEADER:EXHAUSTED

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The reveal pick is an interactive decision, so in production it ends the
#// request and the continuation that reveals, checks the discard pile and draws resumes in a FRESH
#// process. Anything held in an in-memory global between raising the offer and resolving it — the
#// chosen card, a precomputed "should I draw" flag — is gone by then, and the draw silently never
#// happens.
#// Same board and answers as RevealDisaster_Draws, with one boundary inserted.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_180 HMW_193]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2

---

# TwinSuns_OnlyYourOwnDiscardCountsForTheSecondLimb
#// ⚠ THE SEAT-COUNT / SCOPE CELL. "your discard pile" is the RESOLVING player's pile and nobody
#// else's. Here P1 holds no Disaster and has none in their own discard, while EVERY other seat has one
#// sitting in theirs — so P1 must draw nothing and raise no prompt.
#// A check written as "is there a Disaster in any discard pile", or one that reads theirDiscard, or a
#// loop over live seats, draws here. At two seats only P2's pile would be wrong; at four the same
#// mistake has three chances to fire, and seats 3 and 4 are reachable only through WithP3/P4Discard.
#// P1's own pile is seeded with a NON-Disaster so the difference is the TRAIT, not an empty pile.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;discardCardIds:SOR_095;theirDiscardCardIds:SOR_174}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP3Discard: SOR_174
WithP4Discard: HMW_193
WithP1Hand: [HMW_180 SOR_251]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:3
P1NODECISION
