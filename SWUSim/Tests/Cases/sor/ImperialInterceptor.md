# Deals3ToSpace
#// SOR_132 Imperial Interceptor (Space, cost 4) — When Played: you may deal 3 to a
#// space unit. P2's Restored ARC-170 (SOR_044, 2/3, space) is chosen and takes 3 → defeated.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:SOR_132}
P1OnlyActions: true
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
