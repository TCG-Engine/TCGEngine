# ASH_018 Grogu (deployed) — passive: while another friendly unit is defending, it gets +1/+0. P2's
# SOR_046 (3/7) attacks P1's Battlefield Marine (SOR_095, 3/3); the Marine's counter-power rises
# 3->4, so the attacker takes 4 (the Marine still dies to the 3 it takes).

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018:1:1:1
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
