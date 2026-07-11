# SHD_014 Cad Bane (front) — with no enemy unit to damage, the reaction makes no offer (Cad Bane stays
# ready); playing an Underworld card resolves with no prompt.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014}
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1
