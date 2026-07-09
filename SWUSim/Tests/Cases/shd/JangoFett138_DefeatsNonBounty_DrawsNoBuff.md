# SHD_138 Jango Fett — "When this unit attacks and defeats a unit: Draw a card." Attacking the non-Bounty
# SOR_128 (3/1), Jango gets NO +3 and NO Overwhelm (not a Bounty target): it deals 3, defeats SOR_128, and
# no excess spills to the base (P2BASEDMG:0). The defeat draws exactly 1 card (Jango's own ability).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_138:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1HANDCOUNT:1
