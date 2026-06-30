# SEC_038 Condemn — "loses all other abilities" while attacking. P1's SOR_141 (1/3, innate Raid 2)
#   bears 1 Condemn and attacks P2's base from space. P2 declines the granted disclose (so no -6/-0),
#   but the unit's OWN Raid 2 is suppressed by Condemn, so it deals just its base power 1 (not 1+2=3).
#   Proves the lose-all-other-abilities suppresses the host's own keywords.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
