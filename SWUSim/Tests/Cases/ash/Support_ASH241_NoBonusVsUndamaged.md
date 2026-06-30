# ASH_241 Marrok's Fiend Fighter — the +2/+0 applies ONLY while attacking a DAMAGED unit. Attacking an
# undamaged JTL_069 (4/7), ASH_241 deals only its base 3.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: JTL_069:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:3
