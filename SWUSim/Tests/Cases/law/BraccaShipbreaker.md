# OnAttackMill
#// LAW_192 Bracca Shipbreaker (4/3) — On Attack: discard a card from your deck. Attacks the base; mills 1.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_192:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeckNoMill
#// LAW_192 Bracca Shipbreaker — On Attack with an EMPTY deck there is nothing to discard; the attack still
#// resolves. Attacks the enemy base for 4 (combat); P1 discard stays empty.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_192:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DISCARDCOUNT:0
P2BASEDMG:4

---

# MillsTheDeckOfWhoeverCONTROLSIt
#// COVERAGE: offer=N/A (nothing is chosen — the discard is the top card of a named deck and there is no
#//           target) · reqboundary=N/A (the On Attack ability opens no decision, so no answer has to
#//           survive a request boundary) · control=MillsTheDeckOfWhoeverCONTROLSIt ·
#//           boundary=OnAttackMill vs EmptyDeckNoMill (deck stocked / empty) · decline=N/A (mandatory —
#//           no "you may").
#// LAW_192 — "discard a card from YOUR deck" resolves from the unit's CONTROLLER, not its owner. On a
#// normal board those are the same player and the distinction is invisible, so here Bracca sits in P1's
#// ground arena while being OWNED by P2 (the end state after a control-take). The two decks are stocked
#// with DIFFERENT cards so the end state names which one was actually touched: P1's deck empties and P1's
#// discard gains SOR_237, while P2's deck still holds SOR_046 and P2's discard is empty. An owner-scoped
#// deck lookup would have milled P2's SOR_046 instead — and OnAttackMill, where P1 both owns and controls
#// Bracca, would pass unchanged either way.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_192:2
WithP1Deck: SOR_237
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_237
P2DECKCOUNT:1
P2DISCARDCOUNT:0
