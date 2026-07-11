# SHD_048 Gentle Giant (6-cost 2/8 ground, Heroism/Vigilance, Wookiee) — Grit (auto) + "On Attack: You
# may heal damage from another unit equal to the damage on this unit." Gentle Giant has 3 damage (Grit →
# 5 power). On attacking, it may heal another unit by 3. The target SOR_046 (7 HP) has 5 damage → healed
# by exactly 3 (own damage, not the target's full 5) → ends at 2 damage. Gentle Giant keeps its own damage.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_048:1:3
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_048
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:2
