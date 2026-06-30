# LOF_097 Eeth Koth (5/4) — When Defeated: may use the Force → put this card into play as a resource.
# Eeth attacks a 4/7 and dies to the 4 counter-damage; on death P1 uses the Force and Eeth enters the
# resource zone (not the discard pile).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 2
WithP1GroundArena: LOF_097:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1RESCOUNT:3
P1DISCARDCOUNT:0
