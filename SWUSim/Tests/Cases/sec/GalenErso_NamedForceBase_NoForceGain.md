# SEC_046 Galen Erso — naming an opponent's Force base denies its "When a friendly Force unit attacks:
# The Force is with you" ability. P2's base is Starlight Temple (LOF_024, a Force base). P1 plays Galen
# and names "Starlight Temple". When P2 attacks with a Force unit, P2 does NOT gain the Force.

## GIVEN
P1LeaderBase: SOR_005/SOR_020
P2LeaderBase: SOR_010/LOF_024
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: LOF_231:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Starlight Temple
- P2>AttackGroundArena:0:BASE

## EXPECT
P2NOFORCE
