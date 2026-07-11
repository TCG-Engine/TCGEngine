# SHD_008 Boba Fett (front) — a played unit with NO keywords does not trigger the reaction: Boba stays
# ready and no buff is offered (SOR_046 is vanilla).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
