# SWUSim Replay Schema
Simple Full Game
## GIVEN
P1LeaderBase: SOR_014/SOR_024
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitRng: 1 # this means RNG chose Player 1 for Initiative choice
InitChoice: 1 # this means Player 1 decided to take the Initiative from the RNG
DeckSeed: abc123
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Claim
- P2>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Claim
- P2>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P2>AttackGroundArena:1:BASE
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Claim
- P2>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P2>AttackGroundArena:1:BASE
- P1>AttackGroundArena:2:BASE
- P2>AttackGroundArena:2:BASE
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Claim
- P2>Pass
- P1>ResourceHand:0
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P2>AttackGroundArena:1:BASE
- P1>AttackGroundArena:2:BASE
- P2>AttackGroundArena:2:BASE
- P1>AttackGroundArena:3:BASE

## EXPECT
P1WIN
P2BASEDMG:30
P1BASEDMG:27
P1RESCOUNT:6
P2RESCOUNT:5