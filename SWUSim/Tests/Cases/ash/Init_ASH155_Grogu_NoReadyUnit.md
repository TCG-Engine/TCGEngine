# ASH_155 Grogu — with no READY friendly unit to attack with (Grogu itself is exhausted and is the only
# friendly unit), the trigger fizzles cleanly: no dangling decision is left, so P2 takes the next action
# normally (hits P1's base for 3). Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:0:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
