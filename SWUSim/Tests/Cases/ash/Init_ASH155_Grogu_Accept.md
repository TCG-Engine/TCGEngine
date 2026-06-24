# ASH_155 Grogu (2/6) — "When you take the initiative: you may attack with a unit." P1 claims initiative
# with a ready SOR_046 (3/7) in play; Grogu offers a bonus attack → P1 attacks P2's base for 3. The bonus
# attack must NOT swap the turn (the initiative pass already did) — proven by P2 then taking the next
# action (its SOR_046 hits P1's base for 3). Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
