# ASH_253 Yellow Aces Bomber — the "deal 2 to a base" On Attack fires ONLY while upgraded. With no
# upgrade, ASH_253 attacks the enemy base and deals only its 2 combat damage (no decision offered).
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2
