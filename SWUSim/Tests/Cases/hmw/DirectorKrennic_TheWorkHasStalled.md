# OnAttack_BaseUpgraded_DrawsACard
#// HMW_061 (3/4) — "On Attack: If your base is upgraded, draw a card." The first card to read the new
#// base-upgrade state in anger. Krennic attacks the enemy base for 3 and the draw moves one card from
#// deck to hand.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: HMW_061:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# OnAttack_BaseNotUpgraded_NoDraw
#// The condition half: with a bare base the attack still happens but no card is drawn.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: HMW_061:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# OnAttack_OnlyTheEnemyBaseUpgraded_NoDraw
#// "YOUR base" — an upgrade on the opponent's base must not satisfy it. Guards a reader that asks
#// "is any base upgraded" instead of scoping to the attacker's own base.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_095
WithP1GroundArena: HMW_061:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P1DECKCOUNT:2
