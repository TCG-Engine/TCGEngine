# FirstCard_NoExperience
#// COVERAGE: offer=N/A (mandatory, targetless — the Experience tokens always go to this unit; no
#//           choice is ever presented) · decline=N/A (no "you may") · control=StolenUnitStillCounts
#//           (a counted card that changed control before the When Played still counts — the count is
#//           "cards YOU PLAYED", not "cards you control") · boundary=PreviousPhaseNotCounted vs
#//           OtherCards_GetsExperience (phase-reset pair) plus FirstCard_NoExperience (zero edge)
#//           · reqboundary=OpponentCardsNotCounted + CountsPreviousPlayOfItself (the played-this-phase
#//           count is accumulated across several separate actions/serializations before being read)
#// SOR_191 Vanguard Ace — guard: played as the FIRST card this phase → 0 other cards → no Experience
#// tokens. Vanguard stays 1/1 with no subcards.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:1

---

# OtherCards_GetsExperience
#// SOR_191 Vanguard Ace (Space Unit 1/1, cost 2, Cunning/Heroism) — "When Played: For each other card
#// you played this phase, give an Experience token to this unit." P1 plays two throwaways (SOR_210)
#// then Vanguard → 2 other cards this phase → Vanguard gets 2 Experience tokens (+1/+1 each) → 3/3.

## GIVEN
CommonSetup: yyw/yyw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_210
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3

---

# OpponentCardsNotCounted
#// SOR_191 Vanguard Ace — "each other card YOU played this phase": the opponent's plays never count.
#// P1 plays a Swoop Racer, P2 plays its own Swoop Racer, then P1 plays Vanguard Ace → only P1's one
#// other card counts → exactly 1 Experience (2 would mean P2's play was counted).

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:3}
WithP1Hand: SOR_210
WithP1Hand: SOR_191
WithP2Hand: SOR_210

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:2

---

# PreviousPhaseNotCounted
#// SOR_191 Vanguard Ace — the count resets at the phase boundary. P1 plays a Swoop Racer in the
#// FIRST action phase, the round ends, then in the SECOND action phase plays another Swoop Racer
#// followed by Vanguard Ace → only the same-phase Racer counts → exactly 1 Experience (2 would mean
#// the previous phase leaked in). Both decks are seeded so the empty-deck regroup penalty never fires.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_210
WithP1Hand: SOR_191
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:2

---

# StolenUnitStillCounts
#// SOR_191 Vanguard Ace — the count is "cards you PLAYED this phase", not "cards you still control".
#// P1 plays a Swoop Racer (cost 3); P2 plays SOR_122 Traitorous on it (cost 3 or less → P2 takes
#// control of it); P1 then plays Vanguard Ace → the stolen Racer still counts → 1 Experience.
#// ⚠ SOR_122 is COMMAND-aspect — P2's setup code must cover Command or the play silently no-ops.

## GIVEN
CommonSetup: yyw/ggw/{myResources:5;theirResources:5}
WithP1Hand: SOR_210
WithP1Hand: SOR_191
WithP2Hand: SOR_122

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:2

---

# CountsPreviousPlayOfItself
#// SOR_191 Vanguard Ace — a previous play of Vanguard Ace ITSELF this phase counts as an "other
#// card". P1 plays it (no tokens), P2 returns it to P1's hand with SOR_222 Waylay (sole unit →
#// auto-target), and P1 plays it again → its own earlier play counts → exactly 1 Experience.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;theirResources:3}
WithP1Hand: SOR_191
WithP2Hand: SOR_222

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:2

---

# SimulateRequestBoundary_PlayedThisPhaseCountSurvives
#// SOR_191 Vanguard Ace — the Experience grant is mandatory and targetless, so no choose ever ends the
#// request; what crosses the boundary is the "other cards you played this phase" COUNT, accumulated
#// over three separate actions. In production each play is its own request, so that counter must be
#// serialized. Mirrors OtherCards_GetsExperience with a boundary after each throwaway play — Vanguard
#// must still see 2 other cards and arrive 3/3 with 2 Experience tokens.

## GIVEN
CommonSetup: yyw/yyw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_210
WithP1Hand: SOR_191

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
