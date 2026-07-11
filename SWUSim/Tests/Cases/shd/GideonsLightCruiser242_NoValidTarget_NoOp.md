# SHD_242 Gideon's Light Cruiser — control Moff Gideon but hand has no eligible unit → clean fizzle.
# SOR_095 (Heroism, not Villainy) doesn't qualify, so the offer is skipped entirely (no decision).

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1NODECISION
