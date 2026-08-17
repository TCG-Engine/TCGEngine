# OnAttackExhaustReturnUpgrades
#// LAW_224 Liberty (9/7, space, Sentinel) — When Played/On Attack: exhaust an enemy unit and return all
#// upgrades on it that cost 4 or less to their owners' hands. Attacks the base; exhaust SEC_080 and
#// return SOR_120 (cost 2) to P2's hand.

## GIVEN
CommonSetup: yyw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_224:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedExhaustReturn
#// LAW_224 also has a When Played half. Play Liberty from hand; exhaust the enemy SEC_080 and return its
#// cost-3 upgrade SEC_176 to P2's hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedFiltersUpgradeOverFour
#// LAW_224 only returns upgrades costing 4 or less. SEC_080 carries Fulcrum (LAW_150, cost 5) and Sudden
#// Ferocity (SEC_176, cost 3): Fulcrum stays, Sudden Ferocity returns to hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:LAW_150
WithP2GroundArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:1

---

# WhenPlayedAlreadyExhausted
#// LAW_224 still works on an already-exhausted unit: it stays exhausted and its cost-4 Mastery (LAW_129)
#// returns to hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:0:0
WithP2GroundArenaUpgrade: 0:LAW_129
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedTokenUpgradeRemovedNotReturned
#// LAW_224 removes a cost-0 token upgrade (Experience SOR_T01) rather than returning it to hand — the unit
#// is left with no upgrades and P2's hand does not grow.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0

---

# WhenPlayedOffer_AllEnemyUnitsOnly
#// COVERAGE: offer=WhenPlayedOffer_AllEnemyUnitsOnly (pending pool = every enemy unit in both arenas,
#//           friendly units excluded) · decline=N/A (the exhaust is mandatory, no "you may") ·
#//           boundary=WhenPlayedFiltersUpgradeOverFour (cost 4 vs 5 return boundary, with
#//           WhenPlayedAlreadyExhausted covering the exhausted-state edge) ·
#//           control=WhenPlayedEnemyAttachedOwnUpgrade_ReturnsToOwnerHand (upgrade returns to its
#//           OWNER's hand, not its controller's side) · reqboundary=every section resolves the
#//           exhaust-target answer in a request after the play/attack request
#// LAW_224 Liberty — "Exhaust an enemy unit" offers exactly the enemy units, in either arena and any
#// ready state: P2's SEC_080 (ground) and SOR_225 (space, exhausted) are both offered; P1's own SOR_095
#// is not. The decision is left pending so the offer itself is the assertion.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:0:0
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# WhenPlayedWillrowProtectsLoneUpgrade
#// LAW_224 vs SEC_061 Willrow Hood — "While this unit has exactly 1 friendly upgrade on it, that upgrade
#// can't be defeated or returned to hand by enemy card abilities." Liberty exhausts Willrow, but his lone
#// SEC_176 Sudden Ferocity (cost 3, normally returnable) is protected: it stays attached and P2's hand
#// does not grow.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_061
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# WhenPlayedEnemyAttachedOwnUpgrade_ReturnsToOwnerHand
#// LAW_224 returns upgrades to their OWNER's hand — including one the Liberty player attached to an
#// enemy unit. P1 plays SHD_071 Top Target (cost 1) onto P2's SEC_080 (sole unit, attach auto-resolves),
#// then plays Liberty and exhausts SEC_080: Top Target comes back to P1's hand, not P2's.

## GIVEN
CommonSetup: yyw/bgw/{myResources:12}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP1Hand: [SHD_071 LAW_224]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SHD_071
P2HANDCOUNT:0

---

# WhenPlayedPilotUpgrade_FilteredByItsPRINTEDCost_NotItsPilotingCost
#// LAW_224 Liberty — "return all upgrades that cost 4 or less" measures a PILOT upgrade by the card's own
#// printed cost, not by the Piloting cost that was actually paid to attach it. JTL_103 Chewbacca is a
#// cost-5 unit with "Piloting [3 resources]": the two numbers disagree across the 4-or-less line, so the
#// card is the discriminator for which one the filter reads. Attached to P2's SOR_141 Green Squadron
#// A-Wing alongside SEC_176 Sudden Ferocity (cost 3), only Sudden Ferocity comes back — Chewbacca stays
#// on the A-Wing because his printed cost is 5.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2SpaceArena: SOR_141:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103
WithP2SpaceArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_141
P2SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_103
P2HANDCOUNT:1
P2HANDCARD:0:SEC_176

---

# WhenPlayedPilotPlayedForItsPILOTINGCost_StillNotReturned
#// LAW_224 Liberty — the same rule driven through the REAL Piloting dispatch path rather than a seeded
#// upgrade. P2 actually plays JTL_103 Chewbacca with Piloting onto its Green Squadron A-Wing, paying the
#// Piloting cost of 3 (8 resources -> 5 available, so both play modes were affordable and the mode prompt
#// was genuinely offered). 3 is inside Liberty's "4 or less" window, yet Chewbacca must NOT be returned:
#// the filter reads the card's printed cost of 5, never the alternate cost that was paid.

## GIVEN
CommonSetup: yyw/ggw/{myResources:8}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2SpaceArena: SOR_141:1:0
WithP2Hand: JTL_103
WithP1Hand: LAW_224

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2RESAVAILABLE:5
P2SPACEARENAUNIT:0:CARDID:SOR_141
P2SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_103
P2HANDCOUNT:0
