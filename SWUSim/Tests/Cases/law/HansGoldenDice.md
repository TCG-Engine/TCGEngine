# EvenCostNoCredit
#// LAW_225 Han's Golden Dice — guard: if the milled card's cost is EVEN, no Credit is created. The top
#// card is SOR_046 (cost 4, even) → discarded, no Credit.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0

---

# OnAttackOddCostCredit
#// LAW_225 Han's Golden Dice (Upgrade, +0/+0) — grants "On Attack: Discard a card from your deck. If its
#// cost is odd, create a Credit token." SEC_080 wears the Dice and attacks the base; the milled top card
#// is SOR_128 (cost 1, odd) → 1 Credit created.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
