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

---

# EnemyDefeatUpgrade_Confiscate_MovesToGround
#// JTL_094 Luke (pilot upgrade) — the "if this upgrade would be defeated, may move him to ground" replacement
#// fires for ANY defeat of the upgrade, including a plain enemy "defeat an upgrade" ability. P2 plays
#// Confiscate (SOR_251: "Defeat an upgrade") — with one upgrade on one unit it auto-targets Luke. P1 moves
#// Luke to the ground arena as an exhausted unit; SEC_214 survives UNDAMAGED (unlike System Shock, which also
#// deals 1) and keeps no upgrade.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_251
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:JTL_094
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:0

---

# FriendlyDefeatUpgrade_PowerFailure_MayMoveToGround
#// JTL_094 Luke — the replacement is NOT enemy-restricted (contrast JTL_012's enemy-only immunity): a
#// FRIENDLY defeat of the upgrade still lets P1 move him to the ground arena. P1 plays its own Power Failure
#// (SOR_170: "Defeat any number of upgrades on a unit") on its OWN SEC_214, choosing Luke — Luke then moves
#// to the ground arena as an exhausted unit instead of being defeated.

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_170
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:JTL_094
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:1

---

# HostBounce_Waylay_MovesToGround
#// JTL_094 Luke — when the HOST is returned to hand (Waylay, SOR_222: "Return a non-leader unit to its
#// owner's hand"), the pilot upgrade doesn't bounce with it — CR 9.3 defeats it as the host leaves play, so
#// "would be defeated" is satisfied and P1 may move Luke to the ground arena. SEC_214 goes to P1's hand; Luke
#// becomes an exhausted ground unit. (Regression guard: the bounce path formerly discarded the pilot outright
#// without offering the move.)

## GIVEN
CommonSetup: bbk/ybk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:6
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Hand: SOR_222
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1HANDCARD:0:SEC_214
P1DISCARDCOUNT:0

---

# UpgradeBounce_Bamboozle_ReturnsLukeToHand
#// JTL_094 Luke — a BOUNCE of the pilot upgrade itself is NOT a defeat, so the "would be defeated" replacement
#// does NOT fire. Bamboozle (SOR_199: exhaust a unit and return EACH upgrade on it to its owner's hand) sends
#// Luke to P1's HAND (not the ground arena, not the discard); SEC_214 stays in play exhausted with no upgrade.
#// No move-to-ground offer is made (P1 gets no decision).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:6
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Hand: SOR_199
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1HANDCARD:0:JTL_094
P1DISCARDCOUNT:0

---

# CombatHostDefeat_MovesToGround
#// JTL_094 Luke — the replacement also covers a COMBAT defeat of the host (a distinct code path from an
#// ability defeat). SEC_214 (4/4; the harness seats Luke as a plain upgrade, no pilot stat buff) attacks
#// SOR_119 Reinforcement Walker (6/9); the 6 counter damage defeats SEC_214, so Luke would be defeated as the
#// host leaves play. P1 moves him to the ground arena as an exhausted unit; SEC_214 is discarded and SOR_119
#// survives with 4 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094
WithP2GroundArena: SOR_119:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1

---

# StolenHost_UpgradeControllerKeepsPilot_MovesToOwnerGround
#// CR: taking control of a UNIT does not take control of its UPGRADES — an upgrade stays owned/controlled by
#// the player who played it. Full live flow: P1 plays JTL_095 A-Wing, then Luke JTL_094 as a pilot on it; P2
#// plays Traitorous (SOR_122, "take control of a non-leader unit costing ≤3") onto the A-Wing, stealing the
#// UNIT to P2's space arena — but Luke stays P1's upgrade. P1 then plays Confiscate (SOR_251) and defeats
#// Luke: because the replacement follows the UPGRADE's controller (P1), not the host's (P2), P1 — not the
#// opponent — is offered the move, and Luke lands in P1's GROUND arena as an exhausted unit. The stolen A-Wing
#// stays in P2's space arena, now carrying only Traitorous.

## GIVEN
CommonSetup: ggw/ggk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:12;
  theirResources:12
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: JTL_095
WithP1Hand: JTL_094
WithP1Hand: SOR_251
WithP2Hand: SOR_122

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_095
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
