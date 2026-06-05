# SOR_002 Iden Versio — Leader Action: Heal Base
# Enemy unit defeated this phase → heal 1 from P1 base.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_080:2:0
WithP2GroundArena: SOR_128:2:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2
P1LEADER:EXHAUSTED
