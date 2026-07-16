# DetachPilotToGroundUnit
#// JTL_126 Eject — Detach a Pilot upgrade, move it to the ground arena as a unit, and exhaust it. Draw a
#// card. P1's SEC_214 Vehicle carries Paige (JTL_046) as a pilot upgrade. Eject detaches her → she enters
#// the ground arena as an exhausted unit; the host loses the upgrade; P1 draws.

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

---

# LukeCannotShieldOldPilot
#// UID-preservation edge (CANNOT shield): JTL_046 Paige is a pilot upgrade that has been in play since an
#// earlier round (set up in GIVEN, so NO "played this phase" marker). P2 Ejects her → she becomes a P1
#// ground unit, but with no SWU_PLAYED_UNIT marker. P1's Luke (SOR_005) finds no "unit you played this
#// phase" to target, so no Shield is given.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 4
WithP2Resources: 6
WithP2Hand: JTL_126
WithP2Deck: SOR_237
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P2>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# LukeShieldsPilotPlayedThisPhase
#// UID-preservation edge (CAN shield): JTL_046 Paige (Heroism Pilot) is PLAYED as a pilot upgrade THIS
#// phase, so she counts as "a unit you played this phase". P2 Ejects her → she becomes a P1 ground unit
#// with her UniqueID (and SWU_PLAYED_UNIT marker) preserved. P1's Luke (SOR_005) can then shield her.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP2Resources: 6
WithP1Hand: JTL_046
WithP2Hand: JTL_126
WithP2Deck: SOR_237
WithP1GroundArena: SEC_214:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
