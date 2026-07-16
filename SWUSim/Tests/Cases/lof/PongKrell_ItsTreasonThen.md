# CompletesAttack_DefeatWeak
#// LOF_038 Pong Krell (2/9) — Grit + "completes an attack (and survives): may defeat a unit with less
#// remaining HP than this unit's power." Krell (power 2) attacks the base (survives) and defeats the enemy
#// 3/1 (1 HP < 2).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_038:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
