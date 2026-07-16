# UseForce_OpponentDiscards
#// LOF_159 Jedi In Hiding (3/3) — Hidden + When Defeated: may use the Force → each opponent discards a
#// card. It attacks a 4/7 and dies to the counter; on death P1 uses the Force and P2 discards their only
#// card.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_159:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2HANDCOUNT:0
