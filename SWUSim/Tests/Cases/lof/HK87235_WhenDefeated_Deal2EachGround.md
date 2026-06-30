# LOF_235 HK-87 Assassin Droid (4/4) — When Defeated: deal 2 damage to each ground unit. It attacks a 4/7
# and dies; on death its friendly SOR_046 takes 2 and the enemy 4/7 takes 4 (combat) + 2 = 6.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_235:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:6
