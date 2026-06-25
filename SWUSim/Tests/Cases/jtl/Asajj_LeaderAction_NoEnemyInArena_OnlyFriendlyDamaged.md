# JTL_001 Asajj Ventress (leader) — the enemy half is restricted to the SAME arena as the damaged
# friendly unit. The friendly unit is in the GROUND arena; the only enemy unit is in the SPACE arena,
# so after dealing 1 to the friendly there is no enemy to hit in the ground arena — the second half
# fizzles. Proves the "in the same arena" clause.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
