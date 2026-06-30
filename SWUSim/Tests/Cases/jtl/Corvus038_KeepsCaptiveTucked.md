# Captive edge: a unit holding a captive moves to Corvus. JTL_046 Paige first captures SOR_095 (via
# SHD_131 Take Captive), tucking it facedown under her. Then Corvus attaches Paige → her NORMAL upgrades
# + damage are removed, but the captive stays tucked on the Paige pilot subcard (it is NOT released).
# Proof: the captured SOR_095 does NOT return to P2's arena.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 16
WithP1Hand: SHD_131 JTL_038
WithP1GroundArena: JTL_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
