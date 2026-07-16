# DefeatUpgradeEffect_MovesToGround
#// JTL_094 Luke — the "if would be defeated" replacement also covers a "defeat an upgrade" EFFECT (host
#// survives). P2 plays JTL_175 System Shock to defeat the upgrade on SEC_214 (Luke) and deal 1 to that
#// unit. Instead of being defeated, Luke moves to the ground arena as an exhausted unit; SEC_214 stays in
#// play with 1 damage and no upgrade.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 4
WithP2Hand: JTL_175
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:JTL_094
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:0

---

# PilotDefeatDeclined_GoesToDiscard
#// JTL_094 Luke — the move is a "may". P2 defeats SEC_214 (JTL_078); Luke's controller (P1) DECLINES, so
#// Luke is defeated along with his host and goes to P1's discard (both SEC_214 and Luke discarded).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: JTL_078
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# PilotDefeatDeclined_ToDiscard
#// JTL_094 Luke (pilot upgrade) — "If this upgrade would be defeated, you may instead move him to the
#// ground arena as a unit and exhaust him." Luke is attached as a pilot on SEC_214 (owned/controlled by
#// P1). P2 plays JTL_078 to defeat the Vehicle; as the host leaves play Luke would be defeated and P1 is
#// offered the move-to-ground. P1 DECLINES (No) — so Luke is simply defeated and, like every defeated
#// card, goes to his OWNER (P1)'s discard alongside SEC_214. (Discard 2: SEC_214 then JTL_094.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: JTL_078
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SEC_214
P1DISCARDUNIT:1:CARDID:JTL_094

---

# PilotDefeatReplaced_MovesToGround
#// JTL_094 Luke (pilot upgrade) — If this UPGRADE would be defeated, you may instead move him to the
#// ground arena as a unit and exhaust him. Luke is attached as a pilot on SEC_214. P2 plays JTL_078 to
#// defeat the Vehicle SEC_214; as the host leaves play Luke would be defeated, but his controller (P1)
#// moves him to the ground arena as an exhausted unit instead — so SEC_214 is discarded but Luke is not.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: JTL_078
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1
