# SOR_002 Iden Versio — Leader Action: No Heal
# No enemy defeated this phase → leader exhausts but base stays damaged.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED
