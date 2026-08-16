# StolenLandspeeder_DamageDefeatsBeforeWhenPlayed
#// SHD_013 Han Solo (front) — "Action [Exhaust]: Play a unit from your hand. It costs 1 less. Deal 2 damage
#//   to it." — interacting with SHD_161 Stolen Landspeeder (3/2, "When Played: If you played this unit from
#//   your hand, an opponent takes control of it. Bounty - if you own this unit, play it from discard for free
#//   + Exp").
#// The "ideal cheese" would be: let the opponent take control, then damage it under their control so YOU (its
#// owner) collect the bounty for free. It does NOT work: Han's 2 damage resolves FIRST and defeats the 2-HP
#// Landspeeder, so its When Played never fires — the opponent never takes control, and it simply goes to P1's
#// (the owner's) discard. No opponent-controlled unit, no free-bounty replay.
#// COVERAGE: offer=HanSolo_Front_OfferIsAffordableHandUNITSOnly (hand UNITS only, affordability-filtered;
#//           the pilot-mode exclusion is asserted separately in HanSolo_Front_CannotPlayUnitAsAPilotUpgrade
#//           + the deployed twin) · reqboundary=HanSolo_Front_PlayAndDamage_SurvivesRequestBoundary ·
#//           control=this section (the played unit's own "an opponent takes control of it" rider never
#//           fires because Han's 2 damage defeats it first) · boundary=this section (2 HP, dies to the 2
#//           damage) vs HanSolo_Front_PlayDiscountDeal2 (3 HP, survives at 2) · decline=N/A — SWUSim
#//           implements "play a unit from your hand" as a MANDATORY choose, so there is no decline branch
#//           to assert here; the empty-hand no-op is covered by HanSolo_Deployed_EmptyHand_NothingToPlay.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_013;myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_161

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_161
P1LEADER:EXHAUSTED

---

# HanSolo_Deployed_PlayDiscountDeal2
#// SHD_013 Han Solo (deployed Action) — same play-discounted + deal-2. Deployed (5 resources), the
#// deployed Action plays SOR_229 (cost 3 → 2) at index 1 and deals it 2.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# HanSolo_Front_PlayDiscountDeal2
#// SHD_013 Han Solo (front Action [Exhaust]) — "Play a unit from your hand. It costs 1 resource less.
#// Deal 2 damage to it." SOR_229 (cost 3 → 2) is played and takes 2 damage; its discounted cost of 4 (penalized 5, -1) is paid.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:0

---

# HanSolo_Front_OfferIsAffordableHandUNITSOnly
#// SHD_013 Han Solo — "Play a UNIT from your hand": the offer is hand UNITS only, and only ones whose
#// DISCOUNTED cost is payable right now. Hand holds five cards against 2 resources:
#//   SOR_237 Alliance X-Wing  — Unit, Heroism (covered), cost 2 → 1  → OFFERED
#//   SHD_161 Stolen Landspeeder — Unit, Aggression (covered by the leader), cost 1 → 0 → OFFERED
#//   SOR_095 Battlefield Marine — Unit, Command UNCOVERED (+2) → 4 → 3, more than 2 → NOT offered
#//   SOR_246 — an Event, not a unit → NOT offered
#//   SOR_069 — an Upgrade, not a unit → NOT offered
#// Two legal picks keep the choice pending so the offer itself can be read.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_237 SHD_161 SOR_095 SOR_246 SOR_069]
WithP1Resources: 2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1
P1HANDCOUNT:5
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0

---

# HanSolo_Front_CannotPlayUnitAsAPilotUpgrade
#// SHD_013 Han Solo — the ability says "play a UNIT", so a Piloting card taken through Han's discount must
#// enter as a UNIT and may NOT be dispatched down the pilot-as-upgrade path. JTL_197 Anakin Skywalker has
#// Piloting [2 resources Cunning Heroism] and a legal host is sitting in the space arena (SOR_237 Alliance
#// X-Wing, a Vehicle with no pilot) — yet Anakin lands in the GROUND arena as a 2/3 unit carrying Han's 2
#// damage, the X-Wing gains no upgrade, and no play-mode prompt is raised.
#// Cost check: Anakin is Cunning/Heroism, both covered (Cunning base + Heroism leader), so 2 − 1 = 1 of 4.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_197
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_197
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:3
P1HANDCOUNT:0

---

# HanSolo_Deployed_CannotPlayUnitAsAPilotUpgrade
#// SHD_013 Han Solo — the deployed Action carries the same restriction as the front: JTL_197 enters as a
#// ground UNIT with 2 damage, never as a Pilot upgrade on the friendly Vehicle in space. The deployed
#// leader seats at the END of the ground arena, so Han is index 0 and Anakin lands at index 1.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_197
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 4

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_197
P1GROUNDARENAUNIT:1:DAMAGE:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:3
P1HANDCOUNT:0

---

# HanSolo_Deployed_EmptyHand_NothingToPlay
#// SHD_013 Han Solo — with an EMPTY hand the deployed Action has nothing to offer: no prompt is raised, no
#// unit appears, and the resources are untouched. (The board still has a friendly Vehicle in space, so the
#// no-op is "nothing in hand", not "nowhere to put it".)

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 4

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1NODECISION
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1RESAVAILABLE:4
P1HANDCOUNT:0

---

# HanSolo_Front_PlayAndDamage_SurvivesRequestBoundary
#// SHD_013 Han Solo — the unit pick is answered in a SEPARATE request from the one that started the
#// ability, so the play AND the follow-up "deal 2 damage to it" resolve against a gamestate that has been
#// serialized and read back. The marker that ties the 2 damage to the unit just played has to survive that
#// round trip; if it did not, the played unit would land undamaged.
#// Two hand units keep the pick interactive. SOR_237 Alliance X-Wing (Heroism, covered) costs 2 → 1.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_237 SHD_161]
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:3
P1HANDCOUNT:1

---

# HanSolo_Deployed_ActionHasNoExhaustCost_Repeatable
#// SHD_013 Han Solo — the DEPLOYED side is a bare "Action:" with no [Exhaust] (the front's [Exhaust]
#// belongs to the front only), so it is repeatable within a turn and Han stays READY throughout. Three
#// affordable hand units against 12 resources keep both offers interactive so nothing auto-resolves
#// (SOR_237 Heroism covered 2->1; SOR_095 Command uncovered +2 -> 4->3; SOR_046 Vigilance uncovered
#// +2 -> 6->5). Same defect shape as Fennec Shand SHD_016's deployed Action.
#// The per-unit DAMAGE assertions are the second half: each played unit takes its OWN 2. The played
#// unit used to be found by a phase-long marker, so a second use in the same phase re-found the FIRST
#// unit and dealt it another 2 (4 total, killing a 3 HP unit) while the unit just played took none.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_237 SOR_095 SOR_046]
WithP1Resources: 12

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-1
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:3
P1HANDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:DAMAGE:2
P1DISCARDCOUNT:0
