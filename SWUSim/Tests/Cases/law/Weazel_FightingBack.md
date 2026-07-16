# OnAttackGrantRaid
#// LAW_182 Weazel (2/3) — On Attack: another friendly unit gains Raid 2 for this phase. Attacks the
#// base; grant Raid 2 to SOR_095.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_182:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
