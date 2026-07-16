# AttachPilotDefeatsUpgrades
#// JTL_038 Corvus — When Played: may attach a friendly Pilot unit/upgrade to this. (Defeat all upgrades on
#// that Pilot and remove all damage from it.) P1 has Paige (JTL_046) as a UNIT with 2 damage and a normal
#// upgrade (SOR_120). Corvus enters and attaches Paige → her SOR_120 upgrade is defeated (to discard) and
#// her damage cleared; Paige becomes Corvus's only pilot subcard; the ground arena empties.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: JTL_046:1:2
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# KeepsCaptiveTucked
#// Captive edge: a unit holding a captive moves to Corvus. JTL_046 Paige first captures SOR_095 (via
#// SHD_131 Take Captive), tucking it facedown under her. Then Corvus attaches Paige → her NORMAL upgrades
#// + damage are removed, but the captive stays tucked on the Paige pilot subcard (it is NOT released).
#// Proof: the captured SOR_095 does NOT return to P2's arena.

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

---

# RelocatesExistingPilotUpgrade
#// JTL_038 Corvus — "attach a friendly Pilot unit OR upgrade." Here the Pilot (JTL_046 Paige) is ALREADY
#// an upgrade on the Vehicle SEC_214. Corvus relocates that pilot upgrade onto itself: SEC_214 stays as a
#// unit but loses its pilot, and Corvus gains Paige as a pilot subcard. (The Vehicle SEC_214 in P1's
#// ground arena represents "the pilot upgrade on it" in the choose.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
