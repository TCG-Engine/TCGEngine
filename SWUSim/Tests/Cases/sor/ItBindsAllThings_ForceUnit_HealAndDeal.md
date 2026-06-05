# SOR_075 It Binds All Things (Vigilance event, cost 2, Force) — "Heal up to 3 damage from a unit. If
# you control a FORCE unit, you may deal that much damage to another unit." P1 controls a Force unit
# (SOR_049 Obi-Wan). Healing 3 from the damaged SOR_046 (damage 3 → 0) then deals that 3 to the enemy
# LAW_124 (4/7 → DAMAGE:3).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
