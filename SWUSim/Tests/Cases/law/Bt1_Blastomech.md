# OnAttackMillAggressionDeal
#// LAW_173 BT-1 (2/4) — On Attack: discard a card from your deck. If it's Aggression, you may deal 1 to
#// a ground unit. Mills SOR_128 (Aggression) -> deal 1 to the enemy SOR_046.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1

---

# OnAttackMillNonAggressionNoDamage
#// LAW_173 BT-1 — On Attack still discards a card from the deck, but if it is NOT Aggression there is no
#// deal-1 option. Mills SOR_237 (Heroism) -> the card is discarded but no unit takes damage.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# OnAttackEmptyDeckNoPrompt
#// LAW_173 BT-1 — with an empty deck there is nothing to discard, so no deal-1 prompt and no damage; the
#// attack still lands on the enemy base.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:0
P2BASEDMG:2

---

# MillsTheDeckOfWhoeverCONTROLSIt
#// COVERAGE: offer=the deal-1 target is a live pool, exercised by OnAttackMillAggressionDeal (the mill
#//           itself targets nothing — it is the top card of a named deck) ·
#//           reqboundary=MillsTheDeckOfWhoeverCONTROLSIt (a serialize round-trip is inserted between the
#//           mill and the deal-1 answer, so the "was it Aggression?" verdict and the damage source must
#//           survive it) · control=MillsTheDeckOfWhoeverCONTROLSIt ·
#//           boundary=OnAttackMillAggressionDeal vs OnAttackMillNonAggressionNoDamage (Aggression card /
#//           not) and vs OnAttackEmptyDeckNoPrompt (deck empty) · decline=not asserted (the "you may deal
#//           1 damage" rider is declinable but no section PASSes it).
#// LAW_173 — "discard a card from YOUR deck" resolves from the unit's CONTROLLER, not its owner. BT-1
#// sits in P1's ground arena while being OWNED by P2, and the two decks hold DIFFERENT cards so the end
#// state names which one was touched: P1's deck empties and P1's discard gains SOR_128 (Aggression, so
#// the rider fires), while P2's deck keeps its SOR_237 and P2's discard is empty. Had the deck lookup
#// followed the owner it would have milled P2's Heroism SOR_237 — the wrong deck AND the wrong aspect
#// verdict, so the enemy Consular Security Force would have taken no damage at all.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_173:2
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128
WithP2Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128
P2DECKCOUNT:1
P2DISCARDCOUNT:0
