# OnAttack_StealUpgrade
#// SHD_142 Pre Vizsla — the same upgrade-steal fires on the On Attack window too. Deployed Pre Vizsla
#// attacks P2's base; its On Attack lets P1 pay 1 to move SOR_069 off P2's SOR_046 onto Pre Vizsla.
#//
#// COVERAGE: offer=WhenPlayed_OfferSpansBothBoardsAndSkipsVehicleHosts +
#//   WhenPlayed_UnaffordableUpgradeIsNotOffered + OnAttack_UnaffordableUpgradeIsNotOffered +
#//   OnAttack_OwnUpgradeAndVehicleHostAreNotOffered (all four leave the MZMAYCHOOSE PENDING and assert
#//   P1SELECTABLEEXACT over the staged pool) · reqboundary=OnAttack_FreeShieldAbsorbsTheDefendersDamage
#//   (the stolen Shield is read back as a subcard AFTER the attack's decision boundary and still
#//   prevents the defender's damage) · control=WhenPlayed_PayToAttachUpgradeFromAnotherFriendlyUnit +
#//   WhenPlayed_FreeTokenUpgradeCostsNothing (the upgrade changes CONTROLLER: it leaves an enemy /
#//   another friendly host and lands on Pre Vizsla) · boundary=the dispatch-path matrix — every
#//   WhenPlayed_* section has an OnAttack_* twin (attach / free token / cannot-attach / unaffordable),
#//   plus the affordability boundary at capacity 1 (cost-1 and cost-0 in, cost-2 out) ·
#//   decline=WhenPlayed_Decline_NothingMovesAndNothingIsPaid

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_142:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_StealUpgrade
#// SHD_142 Pre Vizsla (Unit, cost 7, Villainy/Aggression, Ground) — "When Played/On Attack: You may pay the
#// cost of an upgrade attached to another non-Vehicle unit. If you do, take control of that upgrade and
#// attach it to this unit, if able." P1 plays Pre Vizsla; P2's SOR_046 wears SOR_069 (cost 1). P1 pays 1
#// and moves SOR_069 onto Pre Vizsla — SOR_046 loses it, Pre Vizsla gains it.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_142
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_OfferSpansBothBoardsAndSkipsVehicleHosts
#// THE OFFER AXIS. "an upgrade attached to ANOTHER NON-VEHICLE unit" has three filters and the pool is
#// the only place all three are visible at once. Board: P1's SOR_179 wears SHD_224 (cost 2), P2 fields
#// SOR_232 (a VEHICLE) wearing SOR_214, TWI_050 wearing a SOR_T02 Shield token (cost 0), and the TWI_T02
#// token trooper wearing TWI_119 (cost 1). The pool must be exactly THREE staged entries — friendly and
#// enemy upgrades alike, a token upgrade included, and SOR_214 excluded purely because its host is a
#// Vehicle. The decision is left PENDING so the offer itself is what is asserted.
#// Staging order is myGround → mySpace → theirGround → theirSpace: 0=SHD_224, 1=SOR_T02, 2=TWI_119.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1&myTempZone-2

---

# WhenPlayed_PayToAttachUpgradeFromAnotherFriendlyUnit
#// Intended: pay SHD_224's printed cost (2) and TAKE CONTROL of it off P1's own SOR_179, attaching it to
#// Pre Vizsla. The source unit is friendly, so this also proves "another unit" means any other unit and
#// not just an enemy's. 10 resources − 7 (Pre Vizsla, on-aspect Villainy/Aggression) − 2 = 1 ready left,
#// and Boba Fett's Armor is +2/+2 so Pre Vizsla reads 10/9.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_142
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_224
P1GROUNDARENAUNIT:1:POWER:10
P1GROUNDARENAUNIT:1:HP:9
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:1
P1NODECISION

---

# WhenPlayed_FreeTokenUpgradeCostsNothing
#// A SOR_T02 Shield token costs 0, so "pay the cost of an upgrade" is a free take. The Shield leaves the
#// enemy TWI_050 and lands on Pre Vizsla with ZERO resources spent — 10 − 7 = 3 ready both before and
#// after the ability. Token upgrades are inside the pool, not filtered out as non-cards.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_142
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_T02
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# WhenPlayed_CannotAttach_DefeatedInstead
#// "…attach it to this unit, IF ABLE. If it can't attach to this unit, DEFEAT it instead." TWI_119
#// Nameless Valor attaches only to a TOKEN unit; Pre Vizsla is a printed unique unit, so he is not a
#// legal host. The cost is still paid (10 − 7 − 1 = 2 ready), the enemy token trooper still loses it,
#// and Pre Vizsla ends up wearing NOTHING — the upgrade goes to its owner's discard pile instead.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-2

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_142
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:POWER:8
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:TWI_119
P1RESAVAILABLE:2
P1NODECISION

---

# WhenPlayed_Decline_NothingMovesAndNothingIsPaid
#// THE DECLINE BRANCH. "You MAY pay the cost" — refusing must be a complete no-op: every upgrade stays
#// on its original host, no resource is exhausted beyond Pre Vizsla's own 7, and no decision is left
#// hanging. Three affordable entries are staged, so this is a genuine refusal and not an empty pool.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_142
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:2:UPGRADECOUNT:1
P2DISCARDCOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# WhenPlayed_UnaffordableUpgradeIsNotOffered
#// THE AFFORDABILITY BOUNDARY, asserted on the OFFER. Pre Vizsla eats 7 of 8 resources, leaving payment
#// capacity 1. SHD_224 costs 2 and must drop OUT of the pool entirely — an unaffordable upgrade is never
#// even shown, rather than shown and then failing to pay. The cost-1 TWI_119 and the cost-0 SOR_T02 both
#// stay in, which is what makes this a boundary and not just a narrowing. Decision left PENDING.
#// With SHD_224 gone the staging renumbers: 0=SOR_T02, 1=TWI_119.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1

---

# WhenPlayed_Unaffordable_LastResourcePaysForTheDefeat
#// The resolution half of the section above (EXPECT reads end state only, so the pending-offer assert
#// and the resolution cannot share a section). P1 spends its single remaining resource on TWI_119,
#// which cannot attach to Pre Vizsla and is therefore defeated. SHD_224 still sits on SOR_179 — the
#// proof that the unaffordable entry was not quietly reachable by another route.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SHD_142
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_224
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0
P2DISCARDUNIT:0:CARDID:TWI_119
P1RESAVAILABLE:0
P1NODECISION

---

# OnAttack_PayToAttachUpgradeBeforeDamage
#// THE DISPATCH-PATH TWIN of WhenPlayed_PayToAttachUpgradeFromAnotherFriendlyUnit. Identical board and
#// identical pick, reached through the On Attack window instead of When Played — the same ability wired
#// to two different dispatch paths is exactly where a one-sided implementation hides.
#// It also pins the TIMING: the On Attack trigger resolves before combat damage (CR 3.4 precedes CR 3.5),
#// so Pre Vizsla is already wearing SHD_224 (+2/+2) when he hits — the base takes 10, not 8.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_224
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1RESAVAILABLE:1
P1NODECISION

---

# OnAttack_FreeShieldAbsorbsTheDefendersDamage
#// The free-token twin, and the request-boundary case: the Shield is taken off the DEFENDER mid-attack
#// and must be readable as Pre Vizsla's own subcard by the time combat damage resolves. Pre Vizsla
#// attacks TWI_050 (4/9) and steals its SOR_T02 for 0. He deals 8 (Luminara survives at 8 damage);
#// Luminara deals 4 back, the just-stolen Shield prevents it and is defeated — so Pre Vizsla ends the
#// attack undamaged and with no upgrades, and P1's resources never moved.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:TWI_050
P2GROUNDARENAUNIT:1:DAMAGE:8
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# OnAttack_CannotAttach_DefeatedInstead
#// The "defeat it instead" twin on the On Attack path. TWI_119 still cannot legally sit on a non-token
#// unit, so paying 1 for it removes it from the enemy token trooper and discards it while Pre Vizsla
#// gains nothing — he hits the base for his printed 8, which is what separates this from the SHD_224
#// case above (10) and proves no phantom buff was applied on the way to the discard pile.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-2

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:8
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:TWI_119
P1RESAVAILABLE:2
P1NODECISION

---

# OnAttack_UnaffordableUpgradeIsNotOffered
#// The affordability boundary on the On Attack path. P1 holds exactly 1 ready resource, so SHD_224
#// (cost 2) drops out of the staged pool while the cost-1 TWI_119 and the free SOR_T02 remain.
#// Decision left PENDING so the pool itself is the assertion; with SHD_224 gone the staging renumbers
#// to 0=SOR_T02, 1=TWI_119.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1
P1RESAVAILABLE:1

---

# OnAttack_Unaffordable_LastResourcePaysForTheDefeat
#// Resolution half of the section above. The last resource buys TWI_119, which cannot attach and is
#// defeated; the unaffordable SHD_224 is still on SOR_179 afterwards. The attack completes normally for
#// 8 to the base, so the narrowed pool did not break the attack it was raised inside.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: [SOR_232:1:0 TWI_050:1:0 TWI_T02:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 2:TWI_119

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-1

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_224
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# OnAttack_OwnUpgradeAndVehicleHostAreNotOffered
#// The two NEGATIVES of "an upgrade attached to ANOTHER NON-VEHICLE unit", isolated. Pre Vizsla himself
#// wears SOR_069 and the enemy SOR_232 is a Vehicle wearing SOR_214 — neither may appear. The only legal
#// entry is SHD_224 on the other friendly unit, so the pool must be exactly one, and it staying PENDING
#// (rather than auto-resolving away) is what lets the pool be read at all.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SHD_142:1:0 SOR_179:1:0]
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArenaUpgrade: 1:SHD_224
WithP2GroundArena: SOR_232:1:0
WithP2GroundArenaUpgrade: 0:SOR_214

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0

---

# WhenPlayed_CreditTokensPayTheUpgradeCost
#// Paying an upgrade's cost is an ordinary resource cost, so Credit tokens cover it (CR 3.13 — "while
#// paying resources you may defeat this token, pay 1 less" is unconditional on what is being paid for).
#// P1 has 7 resources and 4 Credits: Pre Vizsla's own 7 is paid from resources alone (the Credit offer
#// is DECLINED with '-', keeping all four), which leaves ZERO ready resources — so SHD_126 The Darksaber
#// (cost 4) is only reachable if Credits count toward payment capacity. Taking it spends all 4 Credits,
#// strips Sabine, and leaves Pre Vizsla at 12/10 (+4/+3).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 4
WithP1Hand: SHD_142
WithP2GroundArena: SOR_142:1:0
WithP2GroundArenaUpgrade: 0:SHD_126

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_142
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:10
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:0
P1RESAVAILABLE:0
P1NODECISION
