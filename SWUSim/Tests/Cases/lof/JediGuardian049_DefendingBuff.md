# LOF_049 Jedi Guardian (4/8) — "While this unit is defending, it gets +2/+0." When the enemy 4/7 attacks
# it, the Guardian counters for 4+2 = 6 (the attacker takes 6, the Guardian takes 4).

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
