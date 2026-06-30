# SOR_045 Yoda — Restore 2 fires "When this unit attacks" on ANY attack, not just base attacks
# (regression guard for the Restore fix). Yoda attacks a UNIT (SOR_063, 2/4) and survives; P1's base
# heals 2 (3 damage → 1). SOR_063 takes Yoda's 2 combat damage.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2
