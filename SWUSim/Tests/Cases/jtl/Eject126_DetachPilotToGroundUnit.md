# JTL_126 Eject — Detach a Pilot upgrade, move it to the ground arena as a unit, and exhaust it. Draw a
# card. P1's SEC_214 Vehicle carries Paige (JTL_046) as a pilot upgrade. Eject detaches her → she enters
# the ground arena as an exhausted unit; the host loses the upgrade; P1 draws.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_126
WithP1Deck: SOR_095
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1GROUNDARENAUNIT:1:EXHAUSTED
P1HANDCOUNT:1
