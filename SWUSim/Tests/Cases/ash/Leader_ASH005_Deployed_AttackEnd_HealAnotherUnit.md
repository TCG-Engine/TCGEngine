# ASH_005 Luke Skywalker (DEPLOYED unit side) — field observer: fires for ANOTHER friendly unit's attack,
# not just Luke's own. Friendly X-Wing (SOR_237, 2/3) attacks a TIE (SOR_225, 2/1): X-Wing kills the TIE
# and takes 2 counter damage. Luke's deployed ability then heals 2 from that unit (base undamaged → the
# X-Wing is the only valid target → auto-resolves), leaving the X-Wing at 0 damage.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:0
P1BASEDMG:0
