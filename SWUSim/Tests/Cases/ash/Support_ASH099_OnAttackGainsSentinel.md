# ASH_099 Gozanti Assault Carrier (Space, 4/6, Support) — On Attack: this unit gains Sentinel for this
# phase. Gozanti attacks the enemy base; afterward it has Sentinel.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_099:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:ASH_099
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
