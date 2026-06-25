# ASH_018 Grogu (deployed) — passive: while another friendly unit is attacking, the defending unit
# gets -1/-0. P1's Battlefield Marine (SOR_095, 3/3) attacks the enemy wall SOR_046 (3/7); the
# defender's counter-power drops 3->2, so the Marine takes only 2 (survives).

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
