# OnAttack_ReadyExhaustedEnemy
#// SEC_204 Blue Ace (Space, 4/5) — Ambush + On Attack: ready an exhausted enemy unit. SEC_204 attacks
#//   P2's base; on attack the exhausted enemy SOR_046 is readied (single target → auto-resolves).

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:READY

---

# OnAttack_NoExhaustedEnemy_NoOp
#// SEC_204 Blue Ace — the ready has nothing to work on when every enemy unit is already ready, so the
#// attack resolves with no prompt and the enemy board is untouched.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:READY
P1NODECISION
