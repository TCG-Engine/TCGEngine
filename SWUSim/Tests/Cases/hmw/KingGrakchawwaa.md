# OneOtherWookiee_ResourcesOneAndReadiesIt
#// HMW_123 King Grakchawwaa, King of Kashyyyk (Command/Heroism, Wookiee+Official, 6-cost 6/6 Ground) —
#// "When Played: For each other friendly Wookiee unit, resource the top card of your deck.
#//  Ready each card resourced this way."
#// COVERAGE: offer=N/A (no target pick — the count is derived and the cards come off the deck top) ·
#//           negative=NoOtherWookiee_* ("other") + EnemyWookieeDoesNotCount ("friendly") +
#//           NonWookieeFriendlyDoesNotCount (the trait gate) ·
#//           quantity=OneOtherWookiee vs ThreeOtherWookiees vs NoOtherWookiee (0/1/3) ·
#//           boundary=ShortDeck_ResourcesOnlyWhatRemains + EmptyDeck_NothingResourced ·
#//           control=PlayedByP2_UsesP2sOwnDeckAndResources · reqboundary=SurvivesTheRequestBoundary ·
#//           decline=N/A (mandatory, no "you may")
#// ⚠ TWO separate things to prove, and the second is the one an implementation forgets:
#//   (a) the COUNT — one card per OTHER friendly Wookiee;
#//   (b) the READY rider — those cards enter READY, whereas a normal resource enters EXHAUSTED.
#// P1 pays exactly 6, which EXHAUSTS every pre-existing resource, so P1RESAVAILABLE is a clean readout
#// of the rider alone: any ready resource afterwards can only be one the King just made.
#// DECKTOPCARD proves the card came off the TOP.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_123
P1RESCOUNT:7
P1RESAVAILABLE:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046

---

# ThreeOtherWookiees_ResourcesThree
#// HMW_123 — quantity discrimination: THREE other friendly Wookiees resource three cards, all ready.
#// A "resource one if you control any Wookiee" bug passes the one-Wookiee section and fails here.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1GroundArena: SHD_061:1:0
WithP1GroundArena: SHD_200:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_128
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:9
P1RESAVAILABLE:3
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095

---

# NoOtherWookiee_ResourcesNothing_AndDoesNotCountItself
#// HMW_123 — the zero case AND the "other" proof in one. The King is HIMSELF a Wookiee, so a
#// self-counting implementation would resource one card here. Nothing is resourced and nothing readies.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_123
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:2

---

# EnemyWookieeDoesNotCount
#// HMW_123 — "FRIENDLY". Two Wookiees sit on P2's board and none on P1's, so nothing is resourced.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_123
WithP2GroundArena: SHD_211:1:0
WithP2GroundArena: SHD_061:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:2

---

# NonWookieeFriendlyDoesNotCount
#// HMW_123 — the TRAIT gate. Two friendly units that are NOT Wookiees resource nothing.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:2

---

# DeployedWookieeLeaderCounts
#// HMW_123 — a DEPLOYED LEADER is a unit, so a Wookiee leader unit (HMW_009 Chewbacca) counts as
#// "another friendly Wookiee unit" even with no Wookiee units from hand.
#// This is the dispatch-path cell: an implementation that scans only non-leader arena entries, or reads
#// the printed CardType instead of the live object, misses it.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_009:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:1
P1DECKCOUNT:1

---

# ShortDeck_ResourcesOnlyWhatRemains
#// HMW_123 — THREE other Wookiees but only ONE card left in the deck: exactly one card is resourced and
#// readied, and the deck empties without error. Proves the loop is bounded by the deck, not the count.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1GroundArena: SHD_061:1:0
WithP1GroundArena: SHD_200:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:1
P1DECKCOUNT:0

---

# EmptyDeck_NothingResourced_AndNoBaseDamage
#// HMW_123 — an EMPTY deck resources nothing. ⚠ And it deals NO base damage: the empty-deck penalty
#// (CR 6.1, 3 damage) applies to DRAWING, and resourcing is not drawing. Asserting P1BASEDMG:0 is what
#// separates a correct no-op from an implementation that routes through a draw helper.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1Hand: HMW_123

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:0
P1BASEDMG:0

---

# ResourcesComeOffTheTOPInOrder
#// HMW_123 — with TWO other Wookiees the top TWO cards are taken, in order, leaving the third on top.
#// Guards against taking the bottom, or the same slot twice (a stale index after the first move).

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1GroundArena: SHD_061:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:8
P1RESAVAILABLE:2
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128

---

# PlayedByP2_UsesP2sOwnDeckAndResources
#// HMW_123 — "YOUR deck" and "friendly" are the CASTER's. P2 plays the King with its own Wookiee out;
#// P2's resources grow and ready, while P1's deck and resources are untouched.

## GIVEN
CommonSetup: bgw/ggw/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SHD_211:1:0
WithP2Hand: HMW_123
WithP2Deck: SOR_237
WithP2Deck: SOR_046
WithP1Deck: SOR_128
WithP1Deck: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P2RESCOUNT:7
P2RESAVAILABLE:1
P2DECKCOUNT:1
P1DECKCOUNT:2

---

# SurvivesTheRequestBoundary
#// HMW_123 — the request-boundary cell. The When Played resolves with no interactive decision, so the
#// boundary is placed between the two player ACTIONS that write and read the state: the King is played
#// (resourcing and readying), the process is torn down, and the readied resources must still be there
#// to pay for the next card.
#// SOR_095 costs 2 and is ON-ASPECT for this Command/Heroism deck, so it is paid entirely out of the
#// two resources the King just readied — RESAVAILABLE back to 0 is the proof they survived.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1GroundArena: SHD_061:1:0
WithP1Hand: HMW_123
WithP1Hand: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:3:CARDID:SOR_095
P1RESCOUNT:8
P1RESAVAILABLE:0
