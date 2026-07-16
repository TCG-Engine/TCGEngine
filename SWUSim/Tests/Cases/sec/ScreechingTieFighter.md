# OnAttack_GroundLosesKeywords
#// SEC_185 TIE/ln Fighter (Space, 2/1) — On Attack: you may choose a ground unit; it loses its keywords
#//   (and can't gain keywords) for this phase. SEC_185 attacks P2's base; on attack it strips Sentinel
#//   from the enemy ground unit SOR_037.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_185:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
