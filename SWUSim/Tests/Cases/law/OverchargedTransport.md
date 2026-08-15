# WhenPlayedDefeatSpaceUpgrade
#// LAW_195 Overcharged Transport (4/3, space) — When Played/When Defeated: you may defeat an upgrade
#// attached to a space unit. Enemy SOR_237 bears SOR_120; play LAW_195 -> defeat it.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_195

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayedMayDecline
#// LAW_195 Overcharged Transport — the When Played defeat is a "you may". Play it while an enemy space unit
#// wears an upgrade, then decline: the upgrade stays attached.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_195

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeatedDefeatSpaceUpgrade
#// LAW_195 Overcharged Transport — the SECOND trigger window: the same "you may defeat an upgrade
#// attached to a space unit" also fires When Defeated. P2 plays SOR_077 Takedown (defeat a unit
#// with <=5 remaining HP) on P1's Transport (3 HP). The Transport dies and its When Defeated is a
#// cross-player reaction on P1's seat: P1 drains, then defeats the SOR_120 riding P2's SOR_237.
#//
#// COVERAGE: offer=Offer_SpaceUnitsOnly_GroundExcluded (pool pinned pending) · decline=
#//           WhenPlayedMayDecline (PASS keeps the upgrade) · control=NewControllerResolvesWhenDefeated
#//           (the choice follows the controller at defeat) · boundary pair=When Played vs When
#//           Defeated windows (WhenPlayedDefeatSpaceUpgrade vs this section) · reqboundary=this
#//           section (the reaction is a queued builder answered only after P1>Drain, one request
#//           boundary after the defeat).

## GIVEN
CommonSetup: rrw/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 4
WithP2Hand: SOR_077
WithP1SpaceArena: LAW_195:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2

---

# NewControllerResolvesWhenDefeated
#// LAW_195 Overcharged Transport — after a control-change defeat, the When Defeated choice belongs
#// to the NEW controller. P2 plays JTL_043 No Glory, Only Results on P1's Transport (takes control,
#// then defeats it), so P2 gets the "defeat an upgrade attached to a space unit" pick and uses it
#// to strip the SOR_120 from P1's remaining SOR_237. Both the Transport and the upgrade are P1's
#// cards, so both land in P1's discard.

## GIVEN
CommonSetup: rrw/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1SpaceArena: [LAW_195:1:0 SOR_237:1:0]
WithP1SpaceArenaUpgrade: 1:SOR_120

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:2

---

# Offer_SpaceUnitsOnly_GroundExcluded
#// LAW_195 Overcharged Transport — the pick is over units in a SPACE arena that carry upgrades, on
#// EITHER side, and ground hosts are excluded. P1's Wampa wears an upgrade on the GROUND (must be
#// absent from the pool); P1's own SOR_237 and P2's SOR_237 each wear an SOR_120 in space. Playing
#// the Transport leaves the When Played pick PENDING so the pool itself is the assertion: exactly
#// the two space units — the freshly played Transport (no upgrades) and the ground unit are absent.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_195

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0

---

# Offer_PilotUpgradeHostIncluded
#// A PILOT attached as an upgrade IS an upgrade (CR): a space unit whose only attachment is a Piloting
#// card must be offered as a host. P1's SOR_251-style board: friendly SOR_237 wears a real upgrade
#// (SOR_120), enemy SOR_237 carries only the JTL_108 Clone Pilot — BOTH hosts offered, pending.

## GIVEN
CommonSetup: rrk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_195
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0

---

# PilotUpgradeCanBeDefeated
#// Continuation: choosing the pilot-only host defeats the attached Clone Pilot (it goes to its owner's
#// discard as a defeated upgrade; the host X-Wing survives shorn of it).

## GIVEN
CommonSetup: rrk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_195
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1
