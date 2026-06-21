# IBH_051 Tauntaun Mount (reprint of IBH_015) — When Defeated: heal 2 from your base. Confirms duplicate.

## GIVEN
CommonSetup: ggw/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: IBH_051:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
