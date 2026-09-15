# DefendingBuff
#// LOF_049 Jedi Guardian (4/8) — "While this unit is defending, it gets +2/+0." When the enemy 4/7 attacks
#// it, the Guardian counters for 4+2 = 6 (the attacker takes 6, the Guardian takes 4).

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: LOF_049:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# LostAllAbilities_NoDefendingBonus
#// The +2/+0 is an ABILITY, so a Guardian under SOR_138 Force Lightning ("loses all abilities for this
#// phase") counters for its printed 4: the attacking 4/7 ends on 4 damage, not 6. Added 2026-09-14 with
#// the shared while-defending helper, which gates every self-printed "while defending" bonus on it.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithP1GroundArena: LOF_049:1:0:SOR_138
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:4
