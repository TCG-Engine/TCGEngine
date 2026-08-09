# Deal2OwnBaseDraw2
#// TS26_64 Urgent Mission (Event, cost 2, Aggression/Heroism) — Deal 2 damage to your base. Draw 2 cards.
## GIVEN
CommonSetup: rgw/rrk/{myResources:2}
WithP1Hand: TS26_64
WithP1Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:2
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# TheBaseDamageHappensEvenWhenYouCannotDrawTwo
#// TS26_64 Urgent Mission — the two clauses are independent. With only 1 card left, P1 still takes the 2
#// self-damage and draws what it can; the ONE card it could not draw costs 3 more base damage under the
#// empty-deck rule, so the base ends on 5 (2 + 3) with the deck emptied and 1 card drawn.
#// Discriminating: an "only if you can draw 2" reading would leave the base on 0.

## GIVEN
CommonSetup: rgw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP1Deck: [SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1HANDCOUNT:1
P1DECKCOUNT:0
