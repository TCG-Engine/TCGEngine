# SurvivorsGauntlet_OnAttack_MoveUpgrade
#// SHD_064 Survivors' Gauntlet — the On Attack half of the same move ability. Pre-deployed SHD_064
#// (idx0) attacks the base; its On Attack moves SOR_069 from SOR_046 (idx1) to SOR_095 (idx2).
#// COVERAGE: offer=SourceOffer_SpansBothPlayersUpgrades (which upgrades may be picked up) +
#//           EnemyHost_OfferIsSameControllerOnly and
#//           UpgradeYouControlOnAnEnemyHost_DestinationsFollowTheHostsController (where it may go) —
#//           the two pools are built by different filters and are asserted separately ·
#//           decline=Decline_LeavesEveryUpgradeWhereItWas ("you MAY attach") ·
#//           control=UpgradeYouControlOnAnEnemyHost_DestinationsFollowTheHostsController (an upgrade P1
#//           owns and controls, sitting on a P2 unit, still may only move among P2's units) +
#//           MovesUpgradeBetweenTwoENEMYUnits · boundary pair=the "same player" filter is asserted from
#//           both directions (enemy-host offer excludes P1's own units; friendly-host moves stay
#//           friendly) and the attach-restriction leg is paired ineligible/eligible inside one fixture
#//           in RespectsTheMovedUpgradesOwnAttachRestriction ·
#//           reqboundary=SimulateRequestBoundary_DestinationPickAfterTheUpgradePick (the chosen source
#//           host + subcard index and the destination scope are carried across a serialized round-trip)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

---

# SurvivorsGauntlet_WhenPlayed_MoveUpgrade
#// SHD_064 Survivors' Gauntlet — "When Played/On Attack: You may attach an upgrade on a unit to another
#// eligible unit controlled by the same player." When played, P1 moves SOR_069 from SOR_046 (idx0) to
#// SOR_095 (idx1).

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_064
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# SurvivorsGauntlet_MovesUpgradeBetweenTwoENEMYUnits
#// SHD_064 Survivors' Gauntlet — "an upgrade on a unit to another eligible unit controlled by the same
#// player" is not restricted to the Gauntlet controller's own units: P1's Gauntlet may relocate an
#// upgrade sitting on one of P2's units onto ANOTHER P2 unit. The destination scan must therefore be
#// able to offer enemy-controlled units.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# SurvivorsGauntlet_EnemyHost_OfferIsSameControllerOnly
#// SHD_064 — offer assert for the enemy-host case: the upgrade sits on a P2 unit, so "another eligible
#// unit controlled by the same player" narrows the destination pool to P2's OTHER units only. P1's own
#// units are ineligible even though P1 controls the ability, and the source host itself is excluded.
#// Two enemy destinations are seated on purpose so the pick stays interactive (a lone legal target
#// auto-resolves and there would be no offer left to assert).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# SurvivorsGauntlet_SourceOffer_SpansBothPlayersUpgrades
#// SHD_064 — the SOURCE half of the offer: "an upgrade on a unit" names no controller, so EVERY upgrade
#// in play is a candidate, on P1's units and on P2's alike. The sibling section below asserts the
#// destination half; this one is the only place the source pool itself is pinned. The decision is left
#// PENDING (no answer) so the offer is what the end state exposes.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_069
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1.u0&theirGroundArena-0.u0

---

# SurvivorsGauntlet_Decline_LeavesEveryUpgradeWhereItWas
#// SHD_064 — "You MAY attach an upgrade": the decline branch. Same fixture as the offer section above,
#// but P1 answers the optional pick with the choose-nothing token. Both upgrades must stay on their
#// original hosts and the attack must still resolve for the Gauntlet's 4 power.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_069
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:4
P1NODECISION

---

# SurvivorsGauntlet_MovesATokenUpgradeOntoItselfMidAttack
#// SHD_064 — "an upgrade" includes TOKEN upgrades, and the Gauntlet itself is an eligible destination
#// (it is not the source host). An Experience token (SOR_T01, +1/+1) is moved off Consular Security
#// Force onto the attacking Gauntlet during its own On Attack, so the attack that is still in flight
#// lands for 5, not 4 — the buff applies before damage is dealt. A third friendly unit is seated so the
#// destination pick stays interactive rather than auto-resolving onto the Gauntlet.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:5

---

# SurvivorsGauntlet_UpgradeYouControlOnAnEnemyHost_DestinationsFollowTheHostsController
#// SHD_064 — "controlled by the same player" is measured against the upgrade's HOST, not against the
#// player resolving the Gauntlet's ability. P1 first plays Entrenched (SOR_072, an upgrade with no
#// printed attach restriction) onto an ENEMY unit, so P1 owns and controls an upgrade that sits on a
#// P2 unit. When the Gauntlet then moves it, the destination pool is P2's OTHER units — P1's own
#// Gauntlet is NOT offered even though P1 controls both the upgrade and the ability. The decision is
#// left pending so the pool is the assertion.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_072
WithP1GroundArena: SHD_064:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# SurvivorsGauntlet_RespectsTheMovedUpgradesOwnAttachRestriction
#// SHD_064 — the destination must still be a legal host for the upgrade being moved. Hardpoint Heavy
#// Blaster (SOR_121) is printed "Attach to a VEHICLE unit", so Battlefield Marine (idx2, non-Vehicle) is
#// not an eligible destination and the only other Vehicle on the board — the attacking Gauntlet (idx0) —
#// is left as the sole legal target. That narrowing IS the assertion: the destination pick auto-resolves
#// onto the Gauntlet and no decision is left pending. Both Gauntlets are non-unique, so seating two is
#// legal; only idx0 attacks, so only idx0's ability fires.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SHD_064:1:0
WithP1GroundArenaUpgrade: 1:SOR_121
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_121
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1NODECISION

---

# SimulateRequestBoundary_DestinationPickAfterTheUpgradePick
#// SHD_064 — in production the upgrade pick and the destination pick arrive as two separate requests,
#// so the chosen source (host + subcard index) and the "same controller" scope have to survive a
#// round-trip through the serialized gamestate. Same fixture as the two-step move above, with the
#// boundary inserted between the two answers; the upgrade must still land on the unit P1 names, and
#// not back on the host it came from.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADE:0:CARDID:SOR_069
P1NODECISION
