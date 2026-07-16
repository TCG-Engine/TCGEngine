# MysticReflection_Force_Minus2_2
#// SHD_051 Mystic Reflection — with a friendly Force unit (SOR_049, Force/Jedi) in play, the enemy
#// SOR_046 (3/7) gets -2/-2 instead → 1/5.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_051
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# MysticReflection_NoForce_Minus2_0
#// SHD_051 Mystic Reflection — "Give an enemy unit -2/-0 for this phase. If you control a Force unit,
#// give it -2/-2 instead." With NO friendly Force unit, the enemy SOR_046 (3/7) gets -2/-0 → 1/7.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_051
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:7
