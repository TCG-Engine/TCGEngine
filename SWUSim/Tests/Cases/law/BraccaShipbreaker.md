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
