# ASH_241 Marrok's Fiend Fighter (Space, 3/2, Support, Overwhelm) — "This unit gets +2/+0 while attacking
# a damaged unit." Attacks a damaged JTL_069 (4/7, pre-damaged 1): ASH_241 deals 5 (3+2), so JTL_069 ends
# at 6 damage. (Without the bonus it would be 4.)
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: JTL_069:1:1
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:6
