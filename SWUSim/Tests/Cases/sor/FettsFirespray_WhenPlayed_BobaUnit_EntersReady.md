# SOR_184 Fett's Firespray — the "control Boba Fett" check also sees a Boba Fett UNIT in play (not
# just the leader). P1's leader is Thrawn (not Boba), but a Boba Fett unit (SOR_179) is in play →
# Firespray enters READY.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY
