# Reprint051
#// IBH_051 Tauntaun Mount (reprint of IBH_015) — When Defeated: heal 2 from your base. Confirms duplicate.

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

---

# WhenDefeated_HealsBase
#// IBH_015 Tauntaun Mount (Ground, 2/2, Command) — When Defeated: heal 2 damage from your base. The
#//   Tauntaun attacks a 3/3 and dies to the 3 counter; its When Defeated heals P1's base (3 → 1). Driven
#//   as P1's own attack so the trigger resolves inline.

## GIVEN
CommonSetup: ggw/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: IBH_015:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2
