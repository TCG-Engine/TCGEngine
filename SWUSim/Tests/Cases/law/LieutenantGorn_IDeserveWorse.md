# OnAttackTakeCredit
#// LAW_221 Lieutenant Gorn (4/4) — On Attack: take control of an enemy Credit token. Attacks the base;
#// P2 loses its Credit, P1 gains one.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0

---

# EnemyGornTakesBack
#// LAW_221 Lieutenant Gorn — the On-Attack steal works for whichever player attacks, so an opponent's
#// own Gorn can take a Credit right back. Here P2's Gorn attacks P1's base while P1 holds a Credit; P2
#// ends with the Credit, P1 with none.

## GIVEN
CommonSetup: bgw/yyw/{}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1Credits: 1
WithP2GroundArena: LAW_221:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0

---

# OnlyTakesOneOfMultiple
#// LAW_221 Lieutenant Gorn — On Attack takes control of a SINGLE enemy Credit token even when the
#// opponent has several. P2 has 3 Credits; Gorn attacks the base and P1 ends with exactly 1 Credit,
#// P2 keeps the other 2.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 3
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:2

---

# NoEnemyCredits_NothingHappens
#// LAW_221 Lieutenant Gorn — On Attack does nothing when the opponent controls no Credit tokens. P1
#// starts with 1 Credit (to prove no change) and P2 has none; after Gorn attacks the base, credit
#// counts are unchanged.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
