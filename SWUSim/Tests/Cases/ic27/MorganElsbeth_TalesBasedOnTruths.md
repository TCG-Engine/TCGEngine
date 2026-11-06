# CostSix_IsDrawn
#// IC27_179 Morgan Elsbeth — Tales Based On Truths (2, 1/5 Ground, Villainy, Force·Imperial·Night, unique)
#// "On Attack: Reveal the top card of your deck. If it costs 6 or more, draw it. Otherwise, discard it."
#// PREVIEW card (no official rulings). Mandatory (no "may"), no decision. "Draw it" goes through DoDrawCard, so
#// the draw observers fire; "discard it" goes through SWUMillTopCard (From=DECK). Only the TOP card is read.
#//
#// COVERAGE: offer=N/A (no choice is made — reveal, then a branch on printed cost) ·
#//           decline=N/A (no "you may") ·
#//           boundary=CostSix_IsDrawn vs CostFive_IsDiscarded ·
#//           value-class=CostSixEvent_IsDrawn (any card type; cost is printed cost) ·
#//           scope=OnlyTheTopCard_SecondCardUntouched (no search for a 6+) ·
#//           no-target=EmptyDeck_NothingHappens_NoDeckOutDamage ·
#//           draw-path=Draw_FiresDrawObservers (ASH_169 Axe Woves) ·
#//           control=Control_StolenMorgan_UsesTheControllersDeck ·
#//           blank=Blanked_Morgan_DoesNothing ·
#//           reqboundary=N/A (no decision is raised and nothing is stored: the reveal, the cost read and the
#//           draw/discard all resolve inline inside the On Attack) ·
#//           modes=2P only ("your deck" is the controller's; no player is chosen)
#//
#// Top card JTL_251 Jedi Light Cruiser (cost 6, vanilla) → drawn. Morgan's 1 still lands on the base.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1Deck: [JTL_251 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:JTL_251
P1DECKCOUNT:2
P1DISCARDCOUNT:0
P2BASEDMG:1

---

# CostFive_IsDiscarded
#// The boundary's other half: SHD_152 Desperado Freighter (cost 5, vanilla) on top → discarded from the deck,
#// nothing drawn.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1Deck: [SHD_152 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_152
P1DISCARDUNIT:0:FROM:DECK
P2BASEDMG:1

---

# CostSixEvent_IsDrawn
#// Value class: "costs 6 or more" reads any card's printed cost, not just a unit's. SHD_079 Rival's Fall (event,
#// cost 6) → drawn.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1Deck: [SHD_079 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SHD_079
P1DECKCOUNT:1
P1DISCARDCOUNT:0

---

# OnlyTheTopCard_SecondCardUntouched
#// Scope: only the TOP card is revealed. A cheap Marine on top is discarded; the Jedi Light Cruiser under it is
#// neither drawn nor discarded and is now the top card.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1Deck: [SOR_095 JTL_251]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DECKCOUNT:1
P1DECKTOPCARD:JTL_251

---

# EmptyDeck_NothingHappens_NoDeckOutDamage
#// No target: with an empty deck nothing is revealed, so nothing is drawn — and "draw it" names the revealed
#// card, so there is no failed draw and no deck-out damage. The attack still resolves.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P1BASEDMG:0
P2BASEDMG:1

---

# Draw_FiresDrawObservers
#// "Draw it" is a real draw: ASH_169 Axe Woves ("When you draw 1 or more cards: give an Advantage token to this
#// unit") gets its token. A bare move to hand would skip it.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0
WithP1GroundArena: ASH_169:1:0
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCARD:0:JTL_251
P1GROUNDARENAUNIT:1:CARDID:ASH_169
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# Control_StolenMorgan_UsesTheControllersDeck
#// "your deck" = the CONTROLLER's. P1 controls a Morgan OWNED by P2: P1's top card is revealed and drawn; P2's
#// deck is untouched.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: IC27_179:2
WithP1Deck: [JTL_251 SOR_095]
WithP2Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:JTL_251
P1DECKCOUNT:1
P2DECKCOUNT:2
P2HANDCOUNT:0

---

# Blanked_Morgan_DoesNothing
#// A Morgan that has lost all abilities (SOR_138 marker) reveals nothing: the deck is unchanged.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_179:1:0:SOR_138
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:0
P2BASEDMG:1
