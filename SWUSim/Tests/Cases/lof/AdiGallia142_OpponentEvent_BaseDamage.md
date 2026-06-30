# LOF_142 Adi Gallia (2/4) — "When an opponent plays an event: deal 1 damage to that player's base." P2
# plays Confiscate (a neutral event); Adi deals 1 to P2's base.

## GIVEN
CommonSetup: ggw/rrk/{theirResources:1;theirHandCardIds:SOR_251}
WithActivePlayer: 1
WithP1GroundArena: LOF_142:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2BASEDMG:1
