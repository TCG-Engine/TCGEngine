#// IC27_005 Darth Maul — Mechanism of Menace (Leader, Aggression/Villainy, Force/Sith; deployed 4/5 Ground). PREVIEW card — no
#// official rulings exist; reasoned from the CR and two released analogues: SHD_012 Bo-Katan Kryze (same deployed sentence, "On
#// Attack: You may deal 1 damage to a unit") and IC27_002 Rey (same [1 resource, Exhaust] front shape). TWI_135 Darth Maul's
#// released ruling ("You can use Maul's unit ability to damage himself") backs the reading that "a unit" includes Maul himself.
#//   FRONT:    Action [1 resource, Exhaust]: Deal 1 damage to a unit.
#//             Epic Action: If you control 5 or more resources, deploy this leader.
#//   DEPLOYED: On Attack: You may deal 1 damage to a unit.
#// "A unit" names no side and no "another": any unit in play, either side, either arena, any seat — Maul himself on the deployed
#// side. FRONT with no unit in play: still usable (CR 637.3 — the cost changes the game state), and resolves to nothing.
#// The mock listed Villainy only; the printed card carries Aggression + Villainy (fixed 2026-11-06, pinned by Aspects_…).
#//
#// COVERAGE: FRONT clause deal-1 — pos=Front_DealsOneToTheChosenEnemyUnit · friendly target=Front_CanDamageAFriendlyUnit_DefeatsIt
#//   · offer=Front_OfferPool_EveryUnit_BothSides_BothArenas · lone target=Front_LoneUnit_AutoResolves (mandatory — no decline)
#//   · neg(no unit)=Front_NoUnitInPlay_CostPaid_NoEffect · cannot-pay=Front_NoReadyResource_ActionUnavailable
#//   · credit=Front_ACreditPaysTheResource · shield=Front_ShieldAbsorbsThePing
#//   · action close=Front_ClosesTheAction_TurnPasses (no P1OnlyActions) · reqboundary=Front_RequestBoundary_BeforeTheChoice
#//   · modes=TwinSuns (Front_TwinSuns_FarSeatUnit) + TeamSuns (Front_TeamSuns_TeammateAndBothEnemiesOffered) — "a unit" is
#//     unqualified, so the pool is the whole table; the Team Suns section pins that a teammate's unit is not dropped
#//   · decline=N/A — the effect is mandatory once the Action is paid (no "may") · control=N/A — no owner-scoped zone in the text
#// DEPLOYED clause may-deal-1 — pos=Deployed_DeployThenAttack_DealsOneToAnEnemyUnit (real DeployLeader path)
#//   · decline=Deployed_Decline_NoDamage · offer=Deployed_OfferPool_IncludesMaulHimself · self=Deployed_CanDamageHimself
#//   · lone target still asks=Deployed_OnlyMaulInPlay_StillOffered · order=Deployed_PingDefeatsTheDefenderBeforeCombatDamage
#//   · reqboundary=Deployed_RequestBoundary_BeforeTheChoice · modes=TwinSuns (Deployed_TwinSuns_FarSeatUnit)
#//   · control=N/A — a LEADER unit cannot be taken control of
#// EPIC deploy threshold — boundary pair=Epic_FourResources_CannotDeploy / Epic_FiveResources_Deploys

# Front_DealsOneToTheChosenEnemyUnit
#// Three units in play, so the choice is real. P1 pays 1 resource, exhausts Maul and deals 1 to P2's marine; P1's own trooper is
#// untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_OfferPool_EveryUnit_BothSides_BothArenas
#// The offer, left pending: P1's own ground unit, P2's ground unit and P2's space unit — "a unit" has no side or arena.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1DECISIONTOOLTIP:Deal_1_damage_to_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Front_CanDamageAFriendlyUnit_DefeatsIt
#// P1 aims the ping at its own 3/1 stormtrooper, which is defeated by it (1 damage = its HP).

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_LoneUnit_AutoResolves
#// The effect is mandatory, so with exactly one unit in play the target resolves without a prompt.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Front_NoUnitInPlay_CostPaid_NoEffect
#// No unit anywhere. CR 637.3: the cost changes the game state, so the Action is usable — Maul exhausts and the resource is
#// spent — and resolves to nothing, with no decision raised and no base damaged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0

---

# Front_NoReadyResource_ActionUnavailable
#// The [1 resource] is a cost REQUIREMENT, checked before Maul exhausts: with only an exhausted resource the Action is
#// unavailable — Maul stays ready and the marine is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005}
P1OnlyActions: true
WithP1Resources: 1:SOR_046:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_ACreditPaysTheResource
#// A Credit token pays an ability's resource cost (CR 3.13): 0 ready resources + 1 Credit, and the Action resolves.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005;myResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1CREDITCOUNT:0
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_ShieldAbsorbsThePing
#// The lone unit carries a Shield: the 1 damage is prevented and the Shield is defeated.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# Front_ClosesTheAction_TurnPasses
#// Without P1OnlyActions: the leader Action is P1's action and closes exactly once — the turn passes to P2.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
TURNPLAYER:2
NOEXTRAACTION

---

# Front_RequestBoundary_BeforeTheChoice
#// Front_DealsOneToTheChosenEnemyUnit with a request boundary between the offer and the answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_TwinSuns_FarSeatUnit
#// Three seats: seat 2 and seat 3 each have a marine. "A unit" is unqualified, so the FAR seat's unit is legal — this cannot
#// pass at two seats. P1 pings seat 3's marine.

## GIVEN
CommonSetup3P: rrk/bbw/bbw/{myLeader:IC27_005; myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_TeamSuns_TeammateAndBothEnemiesOffered
#// Team Suns (P1+P3 vs P2+P4): the pool holds teammate P3's unit AND both enemy seats' units. A my+their pool would drop P3.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:IC27_005}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1SELECTABLEHAS:p3GroundArena-0
P1SELECTABLEHAS:p2GroundArena-0
P1SELECTABLEHAS:p4GroundArena-0

---

# Deployed_DeployThenAttack_DealsOneToAnEnemyUnit
#// Real dispatch path: Maul deploys (5 resources) and attacks P2's base. The On Attack is one optional pick; P1 pings P2's
#// marine (three units offered — P1's trooper, Maul, the marine — so the choice is real).

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:1:CARDID:IC27_005
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:4

---

# Deployed_Decline_NoDamage
#// "You may": declining ('-') deals nothing to anyone, and the attack still resolves.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:4
P1NODECISION

---

# Deployed_OfferPool_IncludesMaulHimself
#// The offer, left pending: Maul himself (no "another"), P1's trooper, P2's ground marine and P2's space unit.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005:1:1:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit_to_deal_1_damage
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirSpaceArena-0

---

# Deployed_CanDamageHimself
#// Maul may aim the ping at himself (TWI_135 Darth Maul's ruling, by analogy): he takes 1 and still deals his 4 to the base.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005:1:1:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_005
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4

---

# Deployed_OnlyMaulInPlay_StillOffered
#// Maul is the only unit on the table. An optional pick never auto-resolves, so he is offered (and can be declined) rather
#// than pinging himself unasked.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005:1:1:0}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_PingDefeatsTheDefenderBeforeCombatDamage
#// Maul attacks P2's 3/1 stormtrooper and pings IT: the On Attack resolves before combat damage, so the defender is defeated
#// first — Maul takes no counter-damage and nothing reaches P2's base.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IC27_005:1:1:0}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# Deployed_RequestBoundary_BeforeTheChoice
#// Deployed_DeployThenAttack_DealsOneToAnEnemyUnit with a request boundary between the offer and the pick.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:IC27_005}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4

---

# Deployed_TwinSuns_FarSeatUnit
#// Three seats. Deployed Maul attacks seat 2's base; seat 3 has a marine. The far seat's unit is legal — cannot pass at two
#// seats. P1 pings seat 3's marine.

## GIVEN
CommonSetup3P: rrk/bbw/bbw/{myLeader:IC27_005:1:1:0}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4

---

# Epic_FourResources_CannotDeploy
#// "If you control 5 or more resources": at 4, the Epic Action is refused and Maul stays on his leader side.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4;myLeader:IC27_005}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# Epic_FiveResources_Deploys
#// At exactly 5, Maul deploys as a 4/5 Ground unit.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:IC27_005}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:IC27_005
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5

---

# Aspects_AggressionIcon_NoPenaltyOnAggressionCard
#// The printed leader carries Aggression + Villainy (the first mock had Villainy only). Under a Vigilance base, Maul's two icons
#// cover TWI_133 B1 Attack Platform (Aggression, Villainy; cost 3), so it plays for exactly its printed 3. Without the
#// Aggression icon it would cost 5 and the play would fail.

## GIVEN
CommonSetup: bbk/bbw/{myResources:3;myLeader:IC27_005}
P1OnlyActions: true
WithP1Hand: TWI_133

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_133
P1RESAVAILABLE:0
