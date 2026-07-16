# BuffAndAttack
#// ASH_109 T-6 Shuttle 1974 (Space, 2/6, Sentinel) — Action [Exhaust]: give another unit +2/+2 for this
#// phase. You may attack with that unit. T-6 buffs SOR_095 (3/3 → 5/5); the player attacks the enemy base
#// with it for 5.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5
