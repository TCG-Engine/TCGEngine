# PutsSelfAsResource
#// SOR_126 Resupply (Event, cost 3) — Put this event into play as a resource. Playing it costs
#// 3 (the 3 ready resources are exhausted), then Resupply itself enters the resource zone EXHAUSTED
#// ("into play as a resource" — no "ready" wording → exhausted): resources 3 → 4, all exhausted.
#// COVERAGE: offer=N/A (the event names its own object — "put THIS event into play" — so no
#//           candidate pool is ever built and no decision is raised; guarded by P1NODECISION-free
#//           single-action sections that resolve straight through) ·
#//           decline=N/A (no "you may" and no cost choice: the effect is the whole card, and a
#//           resource play from a public zone is not the declinable hidden-hand family) ·
#//           control=PlayedFromOpponentsResources_ResourceLandsUnderTheCaster (owner ≠ controller:
#//           played out of the OPPONENT's resources, the new resource seats under the CASTER and the
#//           owner's discard stays empty) ·
#//           boundary pair=CostBoundary_ThreeResourcesGlows_TwoDoesNot vs
#//           CostBoundary_TwoResources_Dark (cost 3 exactly), plus the off-aspect pair
#//           OffAspect_CostsTwoMore_StillRampsOne vs OffAspect_FourResources_Dark (5 vs 4) ·
#//           reqboundary=NewResourceReadiesAtRegroup (the new resource is written in one request and
#//           re-read across the pass / regroup / resource-step serialized boundaries)

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:4
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# EntersResourceZone_NotDiscard
#// SOR_126 Resupply — the negative half of "put this event into play as a resource": the event must
#// end up in EXACTLY ONE place. It is NOT also discarded (an event normally goes to the discard on
#// resolution), it leaves no staging residue, and the deck is untouched. Without this, an impl that
#// ramped a resource AND discarded the event would still pass the resource count above.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:4
P1DISCARDCOUNT:0
P1TEMPZONECOUNT:0
P1DECKCOUNT:3
P1HANDCOUNT:0

---

# NewResourceReadiesAtRegroup
#// SOR_126 Resupply — the ramp is real, not cosmetic: the exhausted new resource readies with the rest
#// at the regroup step, so the next action phase opens with FOUR usable resources (3 spent on Resupply
#// itself + the card it became). Both players decline the optional regroup resource, and both decks are
#// seeded past the regroup draw so no empty-deck damage is involved.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1RESCOUNT:4
P1RESAVAILABLE:4

---

# CostBoundary_ThreeResourcesGlows_TwoDoesNot
#// SOR_126 Resupply costs 3. The N-vs-N-1 affordability pair, on-aspect (Command card, Command
#// leader/base): three ready resources light the card up in hand.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126

## EXPECT
P1HANDGLOW:0

---

# CostBoundary_TwoResources_Dark
#// The N-1 partner: two ready resources against a cost of 3 — Resupply cannot pay for itself out of a
#// shortfall, so it stays dark. (Resupply never "pays with itself"; the new resource arrives AFTER the
#// cost is paid, and arrives exhausted.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_126

## EXPECT
P1HANDGLOWNOT:0

---

# OffAspect_CostsTwoMore_StillRampsOne
#// Per CR 5.5.2 an uncovered aspect adds 2 to the cost: Resupply is a [Command] card played under an
#// Aggression/Villainy leader + red base, so it costs 5, not 3. It still ramps exactly ONE resource —
#// the ramp is a flat "this card", never scaled by what was paid. 5 ready → pay 5 → 6 total, 0 ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:0

---

# OffAspect_FourResources_Dark
#// The off-aspect boundary partner: four ready resources is one short of the aspect-penalised cost of 5,
#// so the card is not playable. Pins that the +2 penalty is actually applied (at four resources an
#// unpenalised cost-3 Resupply would glow).

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_126

## EXPECT
P1HANDGLOWNOT:0

---

# PlayedFromOpponentsResources_ResourceLandsUnderTheCaster
#// SOR_126 Resupply — the CONTROL axis. An event has no persistent object to change hands, so the
#// reachable owner-vs-controller reading is a card PLAYED by someone other than its owner: "put THIS
#// event into play as a resource" must put it under the player who PLAYED it, not under its owner.
#// P2 has Resupply sitting in its resource row; P1 plays LAW_066 Tear This Ship Apart and plays it
#// from there for free. Resupply must arrive as a P1 resource (13 → 14) and, as always, EXHAUSTED —
#// P1's ready count is untouched by it. Crucially P2's discard stays EMPTY: the event never reaches
#// its owner's discard on the way, so an impl that only ever looked in its own controller's discard
#// would silently ramp nobody. P2 refills the emptied resource slot from its own deck per LAW_066.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:SOR_126:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1RESCOUNT:14
P1RESAVAILABLE:2
P2RESCOUNT:1
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
P2DECKCOUNT:2
