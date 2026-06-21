# LOF_203 Premonition of Doom — The next time you take the initiative this phase, exhaust all units. P1
# plays it, then claims the initiative; every unit in play (both players') is exhausted.

## GIVEN
P1LeaderBase: JTL_007/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: LOF_203
WithP1Resources: 7
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>Claim

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
