# LAW_012 Sebulba (leader front) — "Action [Exhaust, discard a card from your deck]: A friendly unit
# gains Raid 1 for this phase." Grant Raid 1 to SEC_080, then it attacks the base for 3+1 = 4 (Raid
# gives +1/+0 while attacking).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
