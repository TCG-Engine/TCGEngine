# Offer_EveryUnitCostingSevenOrLess_EitherSide_ReadyOrNot
#// IC27_123 Jyn Erso - Her Father's Revenge (Unit, Ground 8/7, cost 8, [Aggression,Heroism]) — "Saboteur. When Played:
#// Ready up to 3 units with combined cost 7 or less. They each gain Saboteur for this phase."
#//
#// COVERAGE: offer=Offer_EveryUnitCostingSevenOrLess_EitherSide_ReadyOrNot (+ the DECISIONPARAM count cap) ·
#//   positive=ReadyThree_CombinedExactlySeven_EachGainsSaboteur (the budget boundary: 7 accepted) ·
#//   boundary=CombinedEight_TheOverBudgetPickIsRefused (8 refused) / the cost-8 Redemption never offered ·
#//   quantity=FourFreeTokens_OnlyThreeAreReadied ("up to 3", cost-0 token units) · decline=Decline_NothingChanges ·
#//   scope=AnEnemyUnit_IsReadiedAndGainsSaboteur ("units" is unqualified) · CR 1.e=AnAlreadyReadyUnit_StillGainsSaboteur ·
#//   value-class=ADeployedLeaderUnit_CostsItsPrintedSeven · can't-ready=FrozenInCarbonite_StaysExhausted_StillGainsSaboteur ·
#//   keyword effect + reqboundary=GrantedSaboteur_IgnoresSentinel_AfterARequestBoundary · duration=Saboteur_EndsWithThePhase ·
#//   reqboundary (decision)=RequestBoundary_BeforeThePicks · printed=PrintedSaboteur_JynIgnoresSentinel ·
#//   modes=2P,TwinSuns (the unqualified pool spans every seat — TwinSuns_AFarSeatUnitIsOffered; no "friendly"/"enemy",
#//   so Team Suns is the same code path)
#//
#// Jyn's own cost is 8, so she is never offered. Offered: P1's exhausted Consular (4), READY Battlefield Marine (2),
#// exhausted Guerilla Attack Pod (6) and P2's exhausted Imperial Dark Trooper (2). Not offered: P1's Redemption (8).
#// The offer caps at 3 picks.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: [SOR_046:0:0 SOR_095:1:0 SOR_148:0:0]
WithP1SpaceArena: SOR_052:0:0
WithP2GroundArena: SEC_080:0:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&theirGroundArena-0
P1DECISIONPARAM:0|3|myGroundArena-0&myGroundArena-1&myGroundArena-2&theirGroundArena-0

---

# ReadyThree_CombinedExactlySeven_EachGainsSaboteur
#// 4 + 2 + 1 = 7 — exactly the budget. All three ready and gain Saboteur; Jyn entered exhausted and keeps her printed one.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: [SOR_046:0:0 SOR_095:0:0 SOR_128:0:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:2:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:3:CARDID:IC27_123
P1GROUNDARENAUNIT:3:EXHAUSTED
P1GROUNDARENAUNIT:3:HASKEYWORD:Saboteur
P1NODECISION

---

# CombinedEight_TheOverBudgetPickIsRefused
#// 4 + 2 + 2 = 8: the third pick no longer fits and is dropped — it stays exhausted and gains nothing.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: [SOR_046:0:0 SOR_095:0:0 SEC_080:0:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:EXHAUSTED
P1GROUNDARENAUNIT:2:NOTKEYWORD:Saboteur

---

# FourFreeTokens_OnlyThreeAreReadied
#// "Up to 3": four cost-0 Battle Droid tokens fit the budget together, but only three may be chosen.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: [TWI_T01:0:0 TWI_T01:0:0 TWI_T01:0:0 TWI_T01:0:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2&myGroundArena-3
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:2:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:3:EXHAUSTED
P1GROUNDARENAUNIT:3:NOTKEYWORD:Saboteur

---

# Decline_NothingChanges
#// "Up to" — choosing none is legal.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_046:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1NODECISION

---

# AnEnemyUnit_IsReadiedAndGainsSaboteur
#// "units" names no controller: P2's exhausted Imperial Dark Trooper may be chosen — it readies and gains Saboteur.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SEC_080:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# AnAlreadyReadyUnit_StillGainsSaboteur
#// CR 1.e: a ready card can be chosen for a readying effect. Nothing changes orientation, but "they each gain Saboteur"
#// still applies to it.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# ADeployedLeaderUnit_CostsItsPrintedSeven
#// CR: a leader unit's cost is its printed cost — Darth Vader's 7 — so he is a legal pick on his own (exactly the budget)
#// and readies. Under a Villainy leader Jyn costs 8 + 2 (Heroism off-aspect).
## GIVEN
CommonSetup: rrk/rrk/{myResources:10;myLeader:SOR_010:0:1}
P1OnlyActions: true
WithP1Hand: IC27_123
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# FrozenInCarbonite_StaysExhausted_StillGainsSaboteur
#// SHD_193 Frozen in Carbonite: the attached unit can't ready, so the ready fails — but the Saboteur rider is not gated
#// by an "If you do", so the chosen unit still gains it.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_046:0:0
WithP1GroundArenaUpgrade: 0:SHD_193
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# GrantedSaboteur_IgnoresSentinel_AfterARequestBoundary
#// The granted keyword WORKS and survives the request boundary between the two actions: the readied Consular attacks
#// P2's base straight past a Sentinel Guerilla Attack Pod.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_148:1:0:SENTINEL
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Saboteur_EndsWithThePhase
#// "for this phase": once the action phase ends, the granted Saboteur is gone. The section lands in phase RES.
#// Mutation-checked against SWU_DUR_PERM (reds). SWU_DUR_ROUND stays green BY DESIGN: RegroupPhaseStart clears round
#// effects right after its regroup-start triggers, so by RES a phase grant and a round grant read the same.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: SOR_046:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
## EXPECT
PHASEISNOT:MAIN
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# RequestBoundary_BeforeThePicks
#// The pick ends the request; the answer lands in the next one.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_123
WithP1GroundArena: [SOR_046:0:0 SOR_095:0:0 SOR_128:0:0]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:2:HASKEYWORD:Saboteur

---

# PrintedSaboteur_JynIgnoresSentinel
#// The printed keyword: a ready Jyn attacks P2's base past a Sentinel.
## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_123:1:0
WithP2GroundArena: SOR_148:1:0:SENTINEL
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:8
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# TwinSuns_AFarSeatUnitIsOffered
#// Four seats, no teams: "units" spans the whole table, so seat 3's exhausted Consular is a legal pick — it readies and
#// gains Saboteur.
## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IC27_123
WithP3GroundArena: SOR_046:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0
## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:READY
P3GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
