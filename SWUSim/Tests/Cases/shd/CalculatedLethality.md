# DefeatUnit_ExpPerUpgrade
#// SHD_039 Calculated Lethality (4-cost event) — "Defeat a non-leader unit that costs 3 or less. For each
#// upgrade that was on that unit, give an Experience token to a friendly unit." The enemy SEC_080 (cost 3)
#// carries 2 upgrades; it's defeated and P1's only friendly unit SOR_046 receives 2 Experience tokens (→ 5/9).
#// COVERAGE: offer=Offer_NonLeaderUnitsCostingThreeOrLessOnBothSides (pending pool; every exclusion —
#//           4-cost, 8-cost, both deployed leaders — is paired with an included body) · boundary pair=
#//           NoUpgrades_NoExperienceDistributed / OneUpgrade_OneExperience / this section's 2-upgrade
#//           case are the 0-1-2 count ladder, and TokenUpgradesCountToo is the "an upgrade is an
#//           upgrade" leg · control=ControlledEnemyOwnedUnit_IsFriendlyForTheExperience ("friendly" is
#//           read from control, not ownership) · reqboundary=
#//           SimulateRequestBoundary_UpgradeCountSurvivesTheDefeat (the count is scaled by a state that
#//           no longer exists once the defeat resolves) · decline=N/A — neither clause is a "you may":
#//           the defeat is a mandatory choose and the Experience distribution has no choose-nothing
#//           option; the nearest refusal-shaped branches are
#//           NoLegalTarget_NothingHappensButTheEventIsSpent (nothing to defeat, resources still spent)
#//           and LurkingTiePhantom_NotDefeatedButExperienceStillDistributed (a legal pick whose defeat
#//           is refused at resolution while the rider still pays out).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9

---

# Offer_NonLeaderUnitsCostingThreeOrLessOnBothSides
#// SHD_039 — "Defeat a NON-LEADER unit that costs 3 OR LESS" is a printed-cost filter that spans both
#// sides and both arenas. Every exclusion has a matching inclusion on the board: the 2-cost friendly
#// SEC_080 and 2-cost friendly space X-Wing and the 2-cost enemy SOR_095 are in; the 4-cost friendly
#// SOR_046 and the 8-cost enemy SOR_052 are out on cost, and both deployed leaders are out on the
#// non-leader clause. The pick is left PENDING so the offer itself is the assertion. Deployed leaders
#// seat at the END of the ground arena.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_052:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# NoUpgrades_NoExperienceDistributed
#// SHD_039 — "For EACH upgrade that WAS on that unit" scales to zero on a bare body: the 2-cost enemy
#// SEC_080 is the only legal target (the friendly SOR_046 costs 4), so the defeat auto-resolves and
#// nothing is offered afterwards. The friendly unit that would have received a token ends with no
#// upgrades at all.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# OneUpgrade_OneExperience
#// SHD_039 — the low half of the count boundary pair (the file's opening section is the 2-upgrade half).
#// SEC_080 carries exactly one Academy Training; it is defeated and the only friendly unit receives
#// exactly one Experience token, going from 3/7 to 4/8.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8

---

# TwoUpgrades_TokensSplitAcrossDifferentFriendlyUnits
#// SHD_039 — "give AN Experience token to A friendly unit" resolves once PER upgrade, so two upgrades
#// means two independent picks and the tokens may land on different bodies. P1 has two friendly units in
#// two arenas and puts one token on each. The defeat target is a real choice here (the friendly 2-cost
#// X-Wing is also legal) and P1 picks the enemy SEC_080.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4

---

# TokenUpgradesCountToo
#// SHD_039 — Experience and Shield tokens ARE upgrades, so a body wearing one of each still feeds the
#// rider two Experience tokens. SEC_080 carries a Shield and an Experience token, is defeated, and the
#// only friendly unit collects 2 Experience (3/7 → 5/9).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9

---

# LurkingTiePhantom_NotDefeatedButExperienceStillDistributed
#// SHD_039 — SHD_187 can't be defeated by enemy card abilities, and the refusal is at RESOLUTION time,
#// not a targeting restriction: the Phantom is still a legal pick, survives the defeat, keeps both of
#// its upgrades, and the Experience rider still resolves off the upgrade count it had. The 3-cost
#// Phantom is the only legal target here (the friendly SOR_046 costs 4), so the pick auto-resolves, and
#// the only friendly unit collects both tokens.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120
WithP2SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9

---

# NoLegalTarget_NothingHappensButTheEventIsSpent
#// SHD_039 — with every body on the board above the 3-cost line the whole ability fizzles: no defeat, no
#// Experience, no prompt. The event still costs the play — it reaches the discard and the 4 resources
#// are exhausted.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_039
P1RESCOUNT:4
P1RESAVAILABLE:0

---

# ControlledEnemyOwnedUnit_IsFriendlyForTheExperience
#// SHD_039 — "a FRIENDLY unit" is decided by CONTROL, not ownership. P1's only body is a SOR_046 that P2
#// still owns (the end state after a take-control effect); it is the sole Experience recipient and takes
#// the token. It is also above the 3-cost line, so the enemy SEC_080 stays the only legal defeat target
#// and the whole card auto-resolves.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:POWER:4

---

# SimulateRequestBoundary_UpgradeCountSurvivesTheDefeat
#// SHD_039 — "for each upgrade that WAS on that unit" is scaled by a state that no longer exists once
#// the defeat resolves, and in production the defeat pick and each Experience pick are separate
#// requests. The boundary is inserted between the defeat and the first token pick: both tokens must
#// still be handed out, which is only possible if the count was captured rather than re-read.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
