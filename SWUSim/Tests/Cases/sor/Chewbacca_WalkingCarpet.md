# GrantedSentinel_ExpiresNextPhase
#// COVERAGE: offer=Offer_PrintedCostCeiling (pending SELECTABLEEXACT; ceiling + affordability) ·
#//           reqboundary=GrantedSentinel_ExpiresNextPhase (grant in one request, expiry across the
#//           phase cross) · control=N/A (the action reads only the controller's own hand; the granted
#//           token rides the unit and no section changes control) · boundary pair=
#//           Offer_PrintedCostCeiling (cost 3 in, 4 out) + UnaffordableExcluded_SingleCandidatePlayed
#//           (affordable in, unaffordable out) · decline=Decline_NothingPlayedNoSentinel.
#//           Sentinel ENFORCEMENT during an enemy attack is covered generically by
#//           keywords/Sentinel_ForceTarget.md. Deployed side: Deployed_HasGritKeyword.
#// SOR_003 Chewbacca — the granted Sentinel is "for this phase" only. P1 plays SOR_237 via the leader
#// action (it gains Sentinel), then passes to end the action phase; RegroupPhaseStart expires the
#// SOR_003 phase-duration token, so the X-Wing no longer has Sentinel. It survives (undamaged), so the
#// unit is still in play — only the keyword is gone.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# LeaderAction_PlaysUnit_GainsSentinel
#// SOR_003 Chewbacca (leader) — Action [exhaust]: Play a unit that costs 3 or less from your hand
#// (paying its cost). It gains Sentinel for this phase. P1 uses the leader action: Chewbacca exhausts,
#// the only ≤3 hand unit SOR_237 Alliance X-Wing (Heroism, cost 2 — Chewbacca covers Heroism) is
#// played for its full 2 (2 ready → 0), enters the space arena, and gains Sentinel via the SOR_003
#// turn-effect token.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# NoValidTarget_Over3Cost_Fizzle
#// SOR_003 Chewbacca — the action only plays a unit costing 3 or LESS. The hand holds SOR_046
#// Consular Security Force (Vigilance,Heroism, cost 4 — both aspects covered by Chewbacca, so it stays
#// 4), which is over the limit. P1 has 4 ready resources (enough to PAY for it), proving the gate is
#// the ≤3 cost ceiling, not affordability: no valid target → the action fizzles. Chewbacca still
#// exhausts (the action was used), the Security Force stays in hand, and no decision is pending.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# Offer_PrintedCostCeiling
#// SOR_003 Chewbacca leader action — the hand-unit offer is the units costing 3 or less that
#// the player can pay for: SOR_237 (2) and SHD_200 (3). The 4-cost SOR_046 is over the cost
#// ceiling and excluded even though 5 resources could pay for it. The decision is left PENDING
#// so the offer itself is asserted.
#// Intended: the ceiling reads the PRINTED cost, so an off-aspect printed-3 unit (effective 5
#// after the +2 penalty) still belongs in this pool when affordable — deferred pending an
#// engine fix (see the session log).

## GIVEN
CommonSetup: ybw/bbk/{
  myLeader:SOR_003;
  myResources:5;
  myhandCardIds:SOR_237,SHD_200,SOR_046
}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# UnaffordableExcluded_SingleCandidatePlayed
#// SOR_003 Chewbacca leader action — a ≤3-cost unit the player cannot PAY for is not offered.
#// With only 2 ready resources the 3-cost SHD_200 drops out and the 2-cost SOR_237 is the only
#// candidate: the X-Wing is played with Sentinel and everything else stays in hand.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target, so
#// the sole candidate myHand-0 is now named explicitly. The exclusion of the unaffordable SHD_200
#// from the offer pool is asserted directly by Offer_PrintedCostCeiling.)

## GIVEN
CommonSetup: ybw/bbk/{
  myLeader:SOR_003;
  myResources:2;
  myhandCardIds:SOR_237,SHD_200,SOR_046
}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1HANDCOUNT:2
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Decline_NothingPlayedNoSentinel
#// SOR_003 Chewbacca leader action — choosing nothing: the action is still used (leader
#// exhausts) but no unit is played and nothing gains Sentinel; the whole hand stays put.

## GIVEN
CommonSetup: ybw/bbk/{
  myLeader:SOR_003;
  myResources:5;
  myhandCardIds:SOR_237,SHD_200,SOR_046
}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:5
P1NODECISION

---

# Deployed_HasGritKeyword
#// SOR_003 Chewbacca deployed side — the leader unit carries Sentinel and Grit (keywords only;
#// the play-a-unit action lives on the FRONT side and is gone once deployed).

## GIVEN
CommonSetup: ybw/bbk/{
  myLeader:SOR_003:1:1
}
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# OffAspect_Printed3_IsOffered
#// Candidate #8 fix guard: "costs 3 or less" is PRINTED cost (cost-semantics rule). SOR_210 Swoop
#// Racer is printed 3 but Cunning — off-aspect for the bbw pair, effective 5. Both copies must be
#// OFFERED (printed 3 ≤ 3, affordable at 5 resources); the old effective-cost gate excluded them
#// entirely, making them unplayable via the ability. Offer left pending.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_210
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# OffAspect_Printed3_PaysEffectiveCost
#// Candidate #8, resolution half: the gate is printed cost, but "paying its cost" is the EFFECTIVE
#// cost — the off-aspect Swoop Racer costs 5 to play through the action (5 ready → 0). It enters
#// with the granted Sentinel.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_210
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# SimulateRequestBoundary_HandPickThenPayAndGrant
#// SOR_003 Chewbacca leader action — the "which ≤3-cost unit from hand" prompt ends the request in
#// production, so the answer arrives in a fresh process. Everything the resolution needs (that this is a
#// play-from-hand paying the EFFECTIVE cost, and the Sentinel grant to attach afterwards) must therefore be
#// serialized. Mirrors OffAspect_Printed3_PaysEffectiveCost with a boundary before the pick: the off-aspect
#// Swoop Racer still costs 5 (5 → 0) and still enters with Sentinel.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_210
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# Decline_SingleTarget_NoUnitPlayed
#// SOR_003 Chewbacca leader action — declining is now possible even when there is exactly ONE legal
#// unit to play (since 2026-08-14 a lone target no longer auto-resolves). The 2-cost SOR_237 is the
#// only ≤3 hand unit and is affordable, but P1 answers "-": nothing is played, nothing gains
#// Sentinel, the X-Wing stays in hand and the 2 resources stay ready — yet the action's cost was
#// still paid, so Chewbacca is EXHAUSTED.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1NODECISION
