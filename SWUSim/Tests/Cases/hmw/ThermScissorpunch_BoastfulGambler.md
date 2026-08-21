# BothCheap_NoDebuff
#// HMW_223 Therm Scissorpunch - Boastful Gambler ([Cunning], cost 2, 5/5 Ground, Underworld, unique) —
#// "When the action phase starts: Reveal the top card of your deck and an opponent's deck. For each
#//  card that cost 3 or more revealed this way, this unit gets -2/-2 for this phase."
#// COVERAGE: offer=N/A in 2 player — the whole trigger is non-interactive there (nothing to choose;
#//           "an opponent" is forced). The only offer this card can raise is the Twin Suns
#//           choose-which-opponent OPTIONCHOOSE, asserted in TwinSuns_ChoosesWhichOpponentsDeck ·
#//           negative=BothCheap_NoDebuff (0 qualifying) + Blanked_NoTriggerAtAll ·
#//           boundary=BoundaryExactlyThree_Counts vs BoundaryTwo_DoesNotCount (3 vs 2, the printed
#//           threshold) and separately the 0/1/2 COUNT ladder ·
#//           control=ControlChanged_ReadsTheNEWControllersDeck (+ its swapped-decks mirror) ·
#//           reqboundary=RequestBoundary_DebuffSurvivesIntoTheNextAction ·
#//           decline=N/A — nothing is optional: no "may", no "up to". The reveal is mandatory and the
#//           debuff is arithmetic.
#// ⚠ REVEAL is not draw and not mill: both cards STAY on top of their decks. Every section therefore
#//   asserts DECKCOUNT and DECKTOPCARD, which is the only thing separating a correct reveal from an
#//   implementation that peels the cards off.
#// ⚠ COST IS PRINTED cost, so a Token Unit (0) never counts and an alternate/discounted price is
#//   irrelevant — the deck card is never played.
#// Reaching an action-phase START costs a full round: P1>Pass (P2 auto-passes under P1OnlyActions),
#// both resource passes, then Drain. The regroup DRAWS 2 per player first, so the card revealed is the
#// THIRD in each seeded deck — which is why every deck below is 4 cards with the payload at index 2.
#// Here both third cards are cheap (SOR_128 cost 1 / SOR_095 cost 2): count 0, Therm stays 5/5.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_046 SOR_046 SOR_095 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:CARDID:HMW_223
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_095

---

# YourDeckExpensive_MinusTwo
#// HMW_223 — one qualifying card, from the CONTROLLER's own deck (SOR_046 Consular Security Force,
#// cost 4). Count 1 → -2/-2 → 3/3. The opponent's top is cost 1, so it contributes nothing.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1DECKTOPCARD:SOR_046
P2DECKTOPCARD:SOR_128
P1DECKCOUNT:2
P2DECKCOUNT:2

---

# OPPONENTSDeckExpensive_MinusTwo
#// HMW_223 — the mirror of the section above, and the half that proves BOTH decks are read. Here the
#// controller's own top is cheap and only the OPPONENT's is expensive; an implementation that reveals
#// only "your deck" passes YourDeckExpensive_MinusTwo and fails exactly here.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_128 SEC_080]
WithP2Deck: [SEC_080 SEC_080 JTL_069 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1DECKTOPCARD:SOR_128
P2DECKTOPCARD:JTL_069

---

# BothExpensive_MinusFour_TheDebuffSTACKS
#// HMW_223 — quantity discrimination at the top of the ladder, and the stacking cell. "For EACH card"
#// means two qualifying reveals are -4/-4, not -2/-2. A same-source phase debuff applied twice used to
#// DE-DUPE (the SEC_081 Partagaz family), so a 3/3 here would be a real bug; 1/1 is the only correct
#// answer. Together with BothCheap (5/5) and the two single sections (3/3), this pins the full 0/1/2
#// ladder — a formula of "any qualifying card → -2/-2" passes three of the four and fails this one.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1DECKTOPCARD:SOR_046
P2DECKTOPCARD:LAW_124

---

# BoundaryExactlyThree_Counts
#// HMW_223 — the boundary's qualifying half. SOR_097 costs exactly 3, and "3 or more" includes 3.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_097 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1DECKTOPCARD:SOR_097

---

# BoundaryTwo_DoesNotCount
#// HMW_223 — the boundary's lower half, one under the line. Without this partner
#// BoundaryExactlyThree_Counts proves nothing about the number: it would pass for a ">= 2" gate too.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_095 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1DECKTOPCARD:SOR_095

---

# RevealIsPUBLICAndLogged
#// HMW_223 — "Reveal" is public information (unlike Thrawn SOR_016, who LOOKS at the top card and whose
#// log entry is scoped to him alone). Both revealed cards must appear in the shared game log. The deck
#// assertions in every other section already prove the cards were not moved; this one proves the
#// reveal actually happened rather than the cost being read silently.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 JTL_069 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
LOGCONTAINS:Therm Scissorpunch
LOGCONTAINS:Consular Security Force
LOGCONTAINS:Munificent Frigate
P1GROUNDARENAUNIT:0:POWER:1

---

# RecomputesEachPhase_DoesNotAccumulate
#// HMW_223 — the duration cell, in the form that also catches accumulation. Round 1 reveals an
#// expensive card on P1's deck (-2/-2 → 3/3); round 2's reveals are both cheap, so Therm must be back
#// to a clean 5/5. A debuff that never expires reads 3/3 here, and one that expires but re-adds on top
#// of a stale token reads 1/1. Only a per-phase recompute gives 5/5.
#// 6-card decks: 2 drawn per regroup x 2 regroups, so the revealed card is index 2 in round 1
#// (SOR_046, cost 4) and index 4 in round 2 (SEC_080, cost 2). ⚠ The SECOND round needs an explicit
#// P2>Pass — P2's auto-pass under P1OnlyActions only answers a P1 action, not a P1 pass, so without it
#// the chain silently stops after one regroup (measured: deck 4 instead of 2).

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080 SEC_080 SOR_128]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SOR_128]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1DECKTOPCARD:SEC_080
P1DECKCOUNT:2

---

# EmptyOpponentDeck_OnlyYoursIsRevealed
#// HMW_223 — cannot-do for one half of the reveal. P2's deck holds exactly the 2 cards the regroup
#// draws, so it is EMPTY when the trigger fires: nothing is revealed from it and it contributes 0.
#// P1's own expensive top still counts, so the result is -2/-2 and not -4/-4 or a crash.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P2DECKCOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# EnemyTherm_DebuffsONLYItself
#// HMW_223 — "this unit" is the Therm itself, so an enemy copy shrinks on THEIR board and leaves your
#// units untouched. Also confirms the trigger fires for a non-active seat's unit: P2 controls Therm
#// while P1 drives the round.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 JTL_069 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_223
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# ControlChanged_ReadsTheNEWControllersDeck
#// HMW_223 — "YOUR deck" is the CONTROLLER's deck, not the owner's. Therm is owned by P2 and
#// controlled by P1; P1's deck top is expensive and P2's is cheap. Reading the owner's deck would give
#// 0 qualifying (5/5) instead of 1 (3/3). Paired with the swapped-decks mirror below.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_223:2
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_223
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# ControlChanged_SwappedDecks_StillOne
#// HMW_223 — the mirror. Same stolen Therm, decks swapped: now the OWNER's (P2's) deck is the
#// expensive one. It still counts — but as the OPPONENT's deck, not as "your deck" — so the total is
#// still exactly 1 qualifying card and Therm is 3/3. What this pins is that the two reveals are
#// controller-relative: an implementation that read the owner's deck for BOTH halves would double-count
#// here and report 1/1.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_223:2
WithP1Deck: [SEC_080 SEC_080 SOR_128 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_046 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# Blanked_NoTriggerAtAll
#// HMW_223 — a Therm who has lost his abilities does not trigger: no reveal, no debuff. SHD_072
#// Imprisoned is seeded onto him. Both decks are stacked to be maximally expensive, so a live trigger
#// would be unmistakable (-4/-4).

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_072
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# ShrinkDefeat_DamagedThermDiesToMinusFour
#// HMW_223 — interaction with the standard modifiers. Therm carries 2 damage; -4/-4 drops his HP to 1,
#// which is at or below his damage, so the state-based shrink sweep defeats him. This is HP REDUCTION,
#// not damage, so it is unpreventable and shield-independent. Its boundary partner below holds the
#// damage fixed at 2 and changes ONLY the debuff amount, so the pair isolates the -4 from the -2.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:2
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_223

---

# ShrinkDefeat_Boundary_MinusTwoSurvivesAtSameDamage
#// HMW_223 — the survival partner, with the damage held at 2 exactly as above and only the number of
#// qualifying reveals changed (1 instead of 2). -2/-2 leaves HP 3 against 2 damage, so he lives. This
#// is what proves the defeat above came from the SECOND -2/-2 and not merely from "any debuff".

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:2
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_223
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# RequestBoundary_DebuffSurvivesIntoTheNextAction
#// HMW_223 — the request-boundary cell. In 2 player the trigger raises no decision, so the boundary
#// goes between the ACTION that applied the debuff (the phase advance) and the next action that reads
#// it: the -2/-2 must live in serialized TurnEffects, not in a process-local. Same GIVEN/EXPECT as
#// YourDeckExpensive_MinusTwo plus one SimulateRequestBoundary line.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P1>SimulateRequestBoundary
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# TwinSuns_ChoosesWhichOpponentsDeck
#// HMW_223 — "AN opponent's deck" is a CHOICE once there is more than one opponent. With three seats
#// P1 is asked which, and the pick decides the count: P3's top is expensive and P2's is cheap, so
#// picking P3 gives 2 qualifying (own SOR_046 + P3's LAW_124) → 1/1. This is also the only offer this
#// card can raise, so the option labels are asserted while the decision is pending.
#// In 2 player the same seam auto-resolves through PASSPARAMETER, which is why every section above is
#// prompt-free.

#// ⚠ A 3-seat action phase needs EVERY seat to pass (P1OnlyActions' auto-pass covers one opponent
#//   only), and every seat to resource-pass, before the next action phase starts.

## GIVEN
CommonSetup: yyk/rrk
WithSeatOrder: 123
WithActivePlayer: 1
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]
WithP3Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P1>Drain
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1

---

# TwinSuns_OfferListsEveryOpponent
#// HMW_223 — the offer itself, left PENDING. This is the ONE decision this card can raise, so it is
#// the only place the pool is assertable: both opponents must be listed. Answering a seat (above and
#// below) proves the branch; only this proves the pool.

## GIVEN
CommonSetup: yyk/rrk
WithSeatOrder: 123
WithActivePlayer: 1
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]
WithP3Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P1>Drain

## EXPECT
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P1
P1GROUNDARENAUNIT:0:POWER:5

---

# TwinSuns_PickingTheCHEAPOpponent_OnlyOneQualifies
#// HMW_223 — the discrimination partner for the picker. Identical board; P1 names P2 (whose top costs
#// 1) instead of P3 (whose top costs 8), so only P1's own SOR_046 qualifies and Therm is 3/3 rather
#// than 1/1. Without this the picker could ignore the answer and scan a fixed seat.

## GIVEN
CommonSetup: yyk/rrk
WithSeatOrder: 123
WithActivePlayer: 1
WithP1GroundArena: HMW_223:1:0
WithP1Deck: [SEC_080 SEC_080 SOR_046 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SOR_128 SEC_080]
WithP3Deck: [SEC_080 SEC_080 LAW_124 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P1>Drain
- P1>AnswerDecision:P2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
