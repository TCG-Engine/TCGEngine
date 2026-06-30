# ASH_196 Gorian Shard's Corsair itself (Underworld, 6 power) attacks a Shielded space token (JTL_T02,
# 2/2). Its own combat damage is unpreventable, so the Shield is bypassed and the token takes the full 6
# and is defeated. (Its On Attack "deal 2" is declined.)
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: JTL_T02:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2SPACEARENACOUNT:0
