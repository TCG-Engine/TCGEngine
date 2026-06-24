# ASH_155 Grogu — the bonus attack is a "may". P1 claims initiative and DECLINES (AnswerDecision:-), so
# no attack happens (P2's base untouched). The decline path also leaves the turn correct — P2 acts next,
# hitting P1's base for 3. Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:-
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:0
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
