# OnAttack_BaseUpgraded_DrawsACard
#// HMW_061 (3/4) — "On Attack: If your base is upgraded, draw a card." The first card to read the new
#// base-upgrade state in anger. Krennic attacks the enemy base for 3 and the draw moves one card from
#// deck to hand.
#// COVERAGE: offer=N/A — STRUCTURAL: nothing is ever chosen. "If your base is upgraded, draw a card" has
#//           no target on either half; the condition is a board read and the draw is fixed. ·
#//           decline=N/A (no "may", no cost) · reqboundary=N/A (nothing is written across a decision) ·
#//           modes=2P ONLY ("your base" is self-scoped; no player reference, no friendly/enemy wording).

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

---

# StolenKrennic_ReadsTheNEWControllersBase
#// THE CONTROL-CHANGE CELL. "If YOUR base is upgraded" resolves against whoever controls Krennic at the
#// moment he attacks, not against his owner. The three existing sections all leave him on his owner's
#// board, so an implementation that read the OWNER's base passes every one of them.
#// P1 controls a Krennic that P2 still OWNS, and it is P1's base that carries the Fortify upgrade —
#// P2's is bare. The draw happening at all is what proves the new controller's base was read.
## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_061:2
WithP1BaseUpgrade: HMW_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P2BASEDMG:3
