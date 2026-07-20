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

---

# DetachSpacePilot_MovesToGround
#// JTL_126 Eject — the ejected Pilot always lands in the GROUND arena, even off a SPACE host. P1's SOR_237
#// (space Vehicle) carries Paige (JTL_046); Eject detaches her to P1's GROUND arena (exhausted), SOR_237
#// stays in space with no upgrade, and P1 draws.

## GIVEN
CommonSetup: gbk/gbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_126
WithP1Deck: SOR_095
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1

---

# EjectEnemyPilot_MovesToOwnerGround
#// JTL_126 Eject — "Detach a Pilot upgrade" spans BOTH players' arenas. P1 ejects the enemy's pilot: P2's
#// SEC_214 carries Paige (JTL_046, owned by P2); Eject detaches her to P2's (the owner's) GROUND arena as an
#// exhausted unit, and P1 — the event's controller — draws the card.

## GIVEN
CommonSetup: gbk/gbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_126
WithP1Deck: SOR_095
WithP2GroundArena: SEC_214:1:0
WithP2GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_214
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:CARDID:JTL_046
P2GROUNDARENAUNIT:1:EXHAUSTED
P1HANDCOUNT:1

---

# ChooseAmongPilots
#// JTL_126 Eject — with more than one host carrying a Pilot, P1 chooses which. Two ground Vehicles: SEC_214
#// (JTL_046 Paige) and SOR_249 (JTL_141 IG-88). P1 ejects Paige off SEC_214; IG-88 stays attached to SOR_249.

## GIVEN
CommonSetup: gbk/gbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_126
WithP1Deck: SOR_095
WithP1GroundArena: SEC_214:1:0
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:JTL_046
WithP1GroundArenaUpgrade: 1:JTL_141

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_249
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:CARDID:JTL_046
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# NoPilotInPlay_NoDraw
#// JTL_126 Eject — with no Pilot upgrade anywhere, the detach has no legal target: the event fizzles to the
#// discard and the draw (which happens only after a successful detach) does NOT occur.

## GIVEN
CommonSetup: gbk/gbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_126
WithP1Deck: SOR_095
WithP1GroundArena: SEC_214:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_126
