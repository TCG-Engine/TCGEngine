# SOR_015 Boba Fett — "When an enemy unit leaves play" fires on a DIRECT-DEFEAT effect too (not
# just combat/bounce). P1 plays Takedown to defeat P2's 3/1; Boba auto-exhausts to ready a resource.
# (Confirms the leave-play reactions are collected by SWUDefeatUnit, the single effect-defeat point.)

## GIVEN
CommonSetup: byk/brw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_077
WithP1Resources: 4:SOR_128:1,1:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
