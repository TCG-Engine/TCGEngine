# ASH_240 Mandalorian Super Commandos (Ground, 2/5) — While you control a leader unit, this unit gets
# +2/+0. With P1's deployed Cassian Andor leader unit on the board, the Commandos are at power 4.
## GIVEN
CommonSetup: brw/brw/{
  myLeader:SOR_013:1:1;
  myBase:SOR_021;
  theirLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: ASH_240:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_240
P1GROUNDARENAUNIT:0:POWER:4
