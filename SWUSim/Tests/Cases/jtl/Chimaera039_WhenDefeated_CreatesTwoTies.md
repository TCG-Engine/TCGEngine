# JTL_039 Chimaera — "When Defeated: Create 2 TIE Fighter tokens." Chimaera (5/6, pre-damaged to 1 HP)
# attacks a small enemy space unit and dies to the counter; its When Defeated then makes 2 TIE tokens
# (JTL_T01) for its controller. (Active player attacks into a lethal counter — the combat-WhenDefeated
# pattern that doesn't stall the harness.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_039:1:5
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01
