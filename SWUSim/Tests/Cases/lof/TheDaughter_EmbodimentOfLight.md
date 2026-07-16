# BaseHealReaction
#// LOF_252 The Daughter — "When damage is dealt to your base: you may use the Force → heal 2 damage from
#// your base." P1's SOR_046 (3 power) attacks P2's base for 3; P2 controls The Daughter and uses the
#// Force to heal 2, leaving net 1 base damage.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_252:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2NOFORCE
P2BASEDMG:1
