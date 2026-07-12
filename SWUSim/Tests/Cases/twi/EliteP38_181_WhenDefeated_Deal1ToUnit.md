# TWI_181 Elite P-38 Starfighter — the SAME ability fires from When Defeated. It (3/2) attacks JTL_069
# (4/7) and dies to the 4 counter-damage; its When Defeated then deals 1 to the frigate (3 combat + 1 = 4).

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_181:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:4
