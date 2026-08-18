# CreditIfUnderworld
#// LAW_134 Bib Fortuna (Command,Villainy, cost 2) — When Played: if you control another Underworld unit,
#// create a Credit token. P1 controls LAW_124 (Underworld) -> 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_134

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1

---

# NoCreditWithoutUnderworld
#// LAW_134 Bib Fortuna — When Played with NO other friendly Underworld unit in play, no Credit is created.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Hand: LAW_134

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:0

---

# Offer_NA_NoDecisionAnywhereInTheFlow
#// LAW_134 Bib Fortuna — offer axis, triaged as genuinely N/A and pinned. "When Played: If you control
#// ANOTHER Underworld unit, create a Credit token" has no target picker at all: the Underworld clause is a
#// GATE on the controller's own board, and "create a Credit token" produces an untargeted token. The board
#// still seeds the scope violator the gate cares about — P1 controls one Underworld unit (LAW_124) and P2
#// controls one too — and the outcome shows the enemy's Underworld unit neither satisfies nor duplicates
#// anything: exactly one Credit is created, for P1 only, with NO decision raised at any point.
#// COVERAGE: offer=N/A, proven by this section (P1NODECISION on a board with an enemy Underworld unit
#//           present) · decline=N/A (no "you may") · control=N/A (the gate reads "you control", the
#//           Credit is created for the player who played Bib; no control-change path exists on this card)
#//           · boundary pair=CreditIfUnderworld (another Underworld unit) vs NoCreditWithoutUnderworld
#//           (none) · reqboundary=N/A (no decision, so no state is ever read across a request)

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: LAW_134

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
P1NODECISION

---

# CabalistItselfDoesNotCount_AnotherIsRequired
#// LAW_134 Bib Fortuna — "If you control ANOTHER Underworld unit". Bib is himself an Underworld unit, so
#// the self-exclusion is the whole condition when he is the only Underworld unit you control: a friendly
#// non-Underworld unit on the board does not satisfy it and no Credit is created.
#// NoCreditWithoutUnderworld plays him onto a completely EMPTY board, which an implementation that merely
#// counted "any friendly unit" would also fail correctly — this board separates the two.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: LAW_134

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LAW_134
